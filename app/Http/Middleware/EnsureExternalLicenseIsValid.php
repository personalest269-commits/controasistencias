<?php

namespace App\Http\Middleware;

use App\Services\ExternalLicenseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureExternalLicenseIsValid
{
    public function __construct(private ExternalLicenseService $license)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $this->license->ensureSeeded();

        if ($request->routeIs('dashboardIndex') || $request->routeIs('license-client.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        if (!$this->license->isConfigured()) {
            if (!$this->license->shouldBlockWithoutConfig()) {
                return $next($request);
            }
            return $this->deny($request, 'Debes configurar y validar la licencia del sistema.');
        }

        $status = $this->license->validateLicense(false, $request);
        if (($status['ok'] ?? false) === true) {
            return $next($request);
        }

        return $this->deny($request, (string) ($status['message'] ?? 'Licencia inválida o sin activar.'));
    }

    protected function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'ok' => false,
                'status' => 'license_invalid',
                'message' => $message,
            ], 423);
        }

        return redirect()->route('dashboardIndex')->with('license_warning', $message);
    }
}
