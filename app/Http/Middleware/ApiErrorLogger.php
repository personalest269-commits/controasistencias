<?php

namespace App\Http\Middleware;

use App\Services\PgLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiErrorLogger
{
    /**
     * Registrar respuestas API con error (HTTP >= 400) en pg_log.
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        try {
            if ($response->getStatusCode() < 400) {
                return $response;
            }

            $routeName = optional($request->route())->getName();
            $body = $this->extractResponseBody($response);

            $message = sprintf(
                'API error response [%s] %s %s',
                $response->getStatusCode(),
                $request->method(),
                $request->fullUrl()
            );

            $context = [
                'route_name' => $routeName,
                'status_code' => $response->getStatusCode(),
                'request_input' => $request->except(['password', 'password_confirmation', 'token', 'authorization']),
                'response_body' => $body,
            ];

            PgLogService::captureMessage('error', $message, 'api', $context);
        } catch (Throwable $ignore) {
            // Nunca romper la API por fallas en el logger.
        }

        return $response;
    }

    private function extractResponseBody(Response $response): ?array
    {
        try {
            $content = $response->getContent();
            if (!is_string($content) || $content === '') {
                return null;
            }

            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            return ['raw' => mb_substr($content, 0, 4000)];
        } catch (Throwable $ignore) {
            return null;
        }
    }
}
