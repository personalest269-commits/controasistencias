<?php

use App\Services\IdGenerator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!function_exists('pg_t')) {
    /**
     * Traduce una clave usando pg_general_traduccion.
     *
     * - Si no existe traducción, devuelve $default.
     * - Si $default está definido y NO existe registro en ES, crea el registro ES automáticamente.
     */
    function pg_t(string $clave, string $default = ''): string
    {
        $lang = (string) App::getLocale();
        $lang = $lang !== '' ? $lang : 'es';

        // Si la tabla no existe, devolvemos el texto por defecto.
        try {
            if (!Schema::hasTable('pg_general_traduccion')) {
                return $default !== '' ? $default : $clave;
            }
        } catch (Throwable $e) {
            return $default !== '' ? $default : $clave;
        }

        // Auto-seed ES si me dan default.
        if ($default !== '') {
            try {
                $existsEs = DB::table('pg_general_traduccion')
                    ->where('clave', $clave)
                    ->where('idioma_codigo', 'es')
                    ->whereNull('estado')
                    ->exists();
                if (!$existsEs) {
                    DB::table('pg_general_traduccion')->insert([
                        'id' => pg_tr_next_id(),
                        'clave' => $clave,
                        'idioma_codigo' => 'es',
                        'texto' => $default,
                        'estado' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        $cacheKey = "pg_tr:$lang:$clave";
        return Cache::remember($cacheKey, 600, function () use ($clave, $lang, $default) {
            try {
                $row = DB::table('pg_general_traduccion')
                    ->select('texto')
                    ->where('clave', $clave)
                    ->where('idioma_codigo', $lang)
                    ->whereNull('estado')
                    ->first();
                if ($row && isset($row->texto)) {
                    return (string) $row->texto;
                }
            } catch (Throwable $e) {
                // ignore
            }
            return $default !== '' ? $default : $clave;
        });
    }
}

if (!function_exists('tr')) {
    /**
     * Traduce por "texto español" sin definir claves manuales.
     * Genera una clave estable: auto.<md5(texto_es)>
     */
    function tr(string $textoEs): string
    {
        $textoEs = (string) $textoEs;
        $clave = 'auto.' . md5($textoEs);
        $lang = (string) App::getLocale();
        $lang = $lang !== '' ? $lang : 'es';

        // Seed ES siempre (para poder administrar en la pantalla de traducciones)
        try {
            if (Schema::hasTable('pg_general_traduccion')) {
                $existsEs = DB::table('pg_general_traduccion')
                    ->where('clave', $clave)
                    ->where('idioma_codigo', 'es')
                    ->whereNull('estado')
                    ->exists();
                if (!$existsEs) {
                    DB::table('pg_general_traduccion')->insert([
                        'id' => pg_tr_next_id(),
                        'clave' => $clave,
                        'idioma_codigo' => 'es',
                        'texto' => $textoEs,
                        'estado' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    // Mantener ES actualizado (si cambió el texto)
                    DB::table('pg_general_traduccion')
                        ->where('clave', $clave)
                        ->where('idioma_codigo', 'es')
                        ->whereNull('estado')
                        ->update(['texto' => $textoEs, 'updated_at' => now()]);
                }
            }
        } catch (Throwable $e) {
            // ignore
        }

        // Retornar traducción del idioma actual
        $out = pg_t($clave, $textoEs);
        return $out !== '' ? $out : $textoEs;
    }
}

if (!function_exists('pg_tr_next_id')) {
    function pg_tr_next_id(): string
    {
        try {
            return IdGenerator::next('PG_GENERAL_TRADUCCION');
        } catch (Throwable $e) {
            // fallback 10 dígitos
            $rand = (string) random_int(1, 9999999999);
            return str_pad($rand, 10, '0', STR_PAD_LEFT);
        }
    }
}
