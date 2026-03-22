<?php

namespace App\Http\Middleware;

use App\Models\Idioma;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // 1) Tomar locale desde sesión si existe
        $locale = (string) session('locale', '');

        // 2) Si no existe en sesión, tomar por defecto desde BD (tabla idiomas)
        if ($locale === '') {
            try {
                if (Schema::hasTable('pg_idiomas')) {
                    $def = Idioma::query()
                        ->where('activo', true)
                        ->where('por_defecto', true)
                        ->first();
                    if ($def && !empty($def->codigo)) {
                        $locale = (string) $def->codigo;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 3) Fallback
        if ($locale === '') {
            $locale = 'es';
        }

        // 4) Aplicar
        App::setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}
