<?php

namespace App\Http\Middleware;

use App\Models\PgConfiguracion;
use Closure;
use Illuminate\Http\Request;

/**
 * Si el frontend está desactivado (FRONTEND_ACTIVO = N),
 * redirige cualquier ruta NO admin al login.
 */
class FrontendToggle
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Permitir siempre admin, api, y archivos estáticos
            if ($request->is('admin') || $request->is('admin/*') || $request->is('api/*')) {
                return $next($request);
            }

            $enabled = PgConfiguracion::bool('FRONTEND_ACTIVO', true);
            if (!$enabled) {
                return redirect()->route('login');
            }
        } catch (\Throwable $e) {
            // Si falla, no bloquear.
        }

        return $next($request);
    }
}
