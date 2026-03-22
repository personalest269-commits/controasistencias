<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Models\Idioma;

/**
 * Establece el idioma del sistema (locale) a partir de la sesión.
 * - default: 'es'
 * - si existe tabla `idiomas` y hay un idioma por defecto, se usa.
 */
class SetIdioma
{
    public function handle(Request $request, Closure $next)
    {
        // Orden: Session > Cookie > Idioma por defecto (tabla idiomas) > 'es'
        $lang = Session::get('lang');
        if (!$lang) {
            $lang = (string) ($request->cookie('lang') ?? '');
        }

        if (!$lang) {
            $lang = 'es';
            try {
                if (Schema::hasTable('pg_idiomas')) {
                    $default = Idioma::query()
                        ->where('activo', 1)
                        ->where('por_defecto', 1)
                        ->value('codigo');
                    if (!empty($default)) {
                        $lang = (string) $default;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Sanitizar
        if (!in_array($lang, ['es', 'en'], true)) {
            $lang = 'es';
        }

        Session::put('lang', $lang);
        App::setLocale($lang);
        return $next($request);
    }
}
