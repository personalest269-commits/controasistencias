<?php

namespace App\Services;

use App\Models\PgLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PgLogService
{
    /**
     * Captura una excepción a pg_log.
     *
     * Importante: nunca debe romper el flujo de la app.
     */
    public static function captureException(Throwable $e, ?string $channel = null, array $extraContext = []): void
    {
        try {
            if (!self::tableReady()) {
                return;
            }

            if (self::shouldSkipException($e)) {
                return;
            }

            $req = null;
            try {
                $req = request();
            } catch (Throwable $ignore) {
                $req = null;
            }

            $context = array_merge([
                'app_env' => config('app.env'),
                'app_debug' => (bool) config('app.debug'),
            ], $extraContext);

            if ($req) {
                $context['input'] = self::safeRequestInput($req->all());
                $context['headers'] = self::safeHeaders($req->headers->all());
            }

            PgLog::create([
                'level' => self::guessLevel($e),
                'channel' => $channel,
                'message' => self::limitString($e->getMessage(), 65000),
                'exception_class' => get_class($e),
                'exception_code' => self::limitString((string) $e->getCode(), 100),
                'file' => self::limitString($e->getFile(), 255),
                'line' => (int) $e->getLine(),
                'trace' => self::limitString($e->getTraceAsString(), 200000),
                'context' => $context,
                'url' => $req ? self::limitString($req->fullUrl(), 2048) : null,
                'method' => $req ? self::limitString($req->method(), 10) : null,
                'ip' => $req ? self::limitString($req->ip(), 45) : null,
                'user_agent' => $req ? self::limitString($req->userAgent(), 512) : null,
                'usuario_id' => Auth::check() ? (string) Auth::id() : null,
                'estado' => null,
            ]);
        } catch (Throwable $ignore) {
            // Nunca romper el flujo por fallas al guardar el log.
        }
    }

    /**
     * Captura un mensaje (Log::error) a pg_log.
     */
    public static function captureMessage(string $level, string $message, ?string $channel = null, array $context = []): void
    {
        try {
            if (!self::tableReady()) {
                return;
            }

            $req = null;
            try {
                $req = request();
            } catch (Throwable $ignore) {
                $req = null;
            }

            // Contexto seguro
            $ctx = [
                'app_env' => config('app.env'),
                'app_debug' => (bool) config('app.debug'),
            ];
            $ctx = array_merge($ctx, $context);

            // Evitar guardar objetos gigantes (por ejemplo, modelos completos)
            $ctx = self::normalizeContext($ctx);

            PgLog::create([
                'level' => self::limitString($level, 20),
                'channel' => $channel,
                'message' => self::limitString($message, 65000),
                'exception_class' => null,
                'exception_code' => null,
                'file' => null,
                'line' => null,
                'trace' => null,
                'context' => $ctx,
                'url' => $req ? self::limitString($req->fullUrl(), 2048) : null,
                'method' => $req ? self::limitString($req->method(), 10) : null,
                'ip' => $req ? self::limitString($req->ip(), 45) : null,
                'user_agent' => $req ? self::limitString($req->userAgent(), 512) : null,
                'usuario_id' => Auth::check() ? (string) Auth::id() : null,
                'estado' => null,
            ]);
        } catch (Throwable $ignore) {
            // silent
        }
    }

    private static function tableReady(): bool
    {
        try {
            return Schema::hasTable('pg_log');
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function shouldSkipException(Throwable $e): bool
    {
        // Evitar loops si la BD está caída o si el error viene de pg_log.
        try {
            $msg = (string) $e->getMessage();
            if (Str::contains($msg, ['pg_log', 'Base table or view not found', 'SQLSTATE[HY000] [2002]'])) {
                return true;
            }
        } catch (Throwable $ignore) {
            // ignore
        }

        // 404 / 403 / validaciones no son “errores del sistema” normalmente.
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return true;
        }
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return true;
        }
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return true;
        }
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            try {
                return $e->getStatusCode() < 500;
            } catch (Throwable $ignore) {
                // ignore
            }
        }

        return false;
    }

    private static function guessLevel(Throwable $e): string
    {
        // Si hay status >= 500 => error, caso contrario warning
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            try {
                return $e->getStatusCode() >= 500 ? 'error' : 'warning';
            } catch (Throwable $ignore) {
                // ignore
            }
        }
        return 'error';
    }

    private static function safeRequestInput(array $input): array
    {
        $blocked = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'access_token',
            'refresh_token',
            'authorization',
        ];

        foreach ($blocked as $k) {
            if (array_key_exists($k, $input)) {
                $input[$k] = '***';
            }
        }

        return self::normalizeContext($input);
    }

    private static function safeHeaders(array $headers): array
    {
        $blocked = ['authorization', 'cookie', 'x-csrf-token'];
        foreach ($blocked as $k) {
            if (isset($headers[$k])) {
                $headers[$k] = ['***'];
            }
        }
        return self::normalizeContext($headers);
    }

    private static function normalizeContext(array $context): array
    {
        // Convertir objetos a strings para evitar fallas al serializar.
        array_walk_recursive($context, function (&$value) {
            if (is_object($value)) {
                $value = method_exists($value, '__toString') ? (string) $value : ('[object:' . get_class($value) . ']');
            }
            if (is_resource($value)) {
                $value = '[resource]';
            }
        });
        return $context;
    }

    private static function limitString(?string $s, int $max): ?string
    {
        if ($s === null) {
            return null;
        }
        $s = (string) $s;
        if (mb_strlen($s) <= $max) {
            return $s;
        }
        return mb_substr($s, 0, $max);
    }
}
