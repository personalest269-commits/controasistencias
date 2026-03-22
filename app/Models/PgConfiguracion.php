<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use App\Models\Concerns\GeneraIdVarchar;

class PgConfiguracion extends Model
{
    use GeneraIdVarchar;

    public const OBJETO_CONTROL = 'PG_CONFIGURACIONES';

    protected $table = 'pg_configuraciones';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'grupo',
        'activo',
        'estado',
    ];

    public $timestamps = true;

    /**
     * Cache key único.
     */
    protected static function cacheKey(): string
    {
        return 'pg_configuraciones_key_value_v1';
    }

    /**
     * Devuelve todas las configuraciones activas (estado NULL) en un array [clave => valor].
     */
    public static function allKeyValue(int $ttlSeconds = 300): array
    {
        try {
            if (!Schema::hasTable('pg_configuraciones')) {
                return [];
            }

            return Cache::remember(self::cacheKey(), $ttlSeconds, function () {
                return self::query()
                    ->whereNull('estado')
                    ->where('activo', 'S')
                    ->orderBy('grupo')
                    ->orderBy('clave')
                    ->pluck('valor', 'clave')
                    ->toArray();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function clearCache(): void
    {
        try {
            Cache::forget(self::cacheKey());
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Obtener valor por clave.
     */
    public static function valor(string $clave, $default = null)
    {
        $all = self::allKeyValue();
        if (array_key_exists($clave, $all)) {
            return $all[$clave];
        }
        return $default;
    }

    /**
     * Obtener booleano (S/N, 1/0, true/false).
     */
    public static function bool(string $clave, bool $default = false): bool
    {
        $v = self::valor($clave, null);
        if ($v === null) {
            return $default;
        }
        $v = strtoupper(trim((string) $v));
        return in_array($v, ['S', 'SI', '1', 'TRUE', 'T', 'Y', 'YES'], true);
    }

    /**
     * Upsert simple de una configuración.
     */
    public static function setValor(
        string $clave,
        $valor,
        string $tipo = 'texto',
        ?string $descripcion = null,
        string $grupo = 'general',
        string $activo = 'S'
    ): void {
        if (!Schema::hasTable('pg_configuraciones')) {
            return;
        }

        self::query()->updateOrCreate(
            ['clave' => $clave],
            [
                'valor' => is_null($valor) ? null : (string) $valor,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'grupo' => $grupo,
                'activo' => $activo,
                'estado' => null,
            ]
        );

        self::clearCache();
    }

    /**
     * Aplica configuración en runtime (timezone, app.name).
     * Llamar en AppServiceProvider::boot().
     */
    public static function applyRuntime(): void
    {
        if (!Schema::hasTable('pg_configuraciones')) {
            return;
        }

        $tz = (string) self::valor('APP_TIMEZONE', Config::get('app.timezone', 'UTC'));
        if ($tz !== '') {
            Config::set('app.timezone', $tz);
            try {
                date_default_timezone_set($tz);
            } catch (\Throwable $e) {
                // ignore invalid timezone
            }
        }

        $name = (string) self::valor('NOMBRE_SISTEMA', Config::get('app.name', 'Sistema'));
        if ($name !== '') {
            Config::set('app.name', $name);
        }
    }

    public static function formatoFecha(): string
    {
        $fmt = (string) self::valor('FORMATO_FECHA', 'Y-m-d H:i:s');
        return trim($fmt) !== '' ? $fmt : 'Y-m-d H:i:s';
    }

    /**
     * Formato de fecha SIN hora (derivado de FORMATO_FECHA).
     * Ej: 'Y-m-d H:i:s' -> 'Y-m-d', 'd/m/Y H:i' -> 'd/m/Y'
     */
    public static function formatoFechaSolo(): string
    {
        $fmt = self::formatoFecha();
        // Tomar solo la parte antes del primer espacio (convención común).
        if (str_contains($fmt, ' ')) {
            $fmt = explode(' ', $fmt, 2)[0];
        }
        $fmt = trim($fmt);
        return $fmt !== '' ? $fmt : 'Y-m-d';
    }

    public static function formatFecha($date): string
    {
        if (empty($date)) {
            return '';
        }
        try {
            $c = $date instanceof Carbon ? $date : Carbon::parse($date);
            return $c->format(self::formatoFecha());
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    public static function formatFechaSolo($date): string
    {
        if (empty($date)) {
            return '';
        }
        try {
            $c = $date instanceof Carbon ? $date : Carbon::parse($date);
            return $c->format(self::formatoFechaSolo());
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    /**
     * Placeholder amigable para inputs (dd/mm/aaaa, yyyy-mm-dd, etc.)
     */
    public static function placeholderFechaSolo(): string
    {
        $fmt = self::formatoFechaSolo();
        // Conversión simple de tokens PHP a placeholder humano
        $map = [
            'd' => 'dd',
            'j' => 'd',
            'm' => 'mm',
            'n' => 'm',
            'Y' => 'aaaa',
            'y' => 'aa',
        ];
        $out = '';
        $len = strlen($fmt);
        for ($i = 0; $i < $len; $i++) {
            $ch = $fmt[$i];
            $out .= $map[$ch] ?? $ch;
        }
        return $out;
    }

    /**
     * Placeholder amigable para fecha + hora (según FORMATO_FECHA).
     * Ej: "Y-m-d H:i:s" -> "aaaa-mm-dd HH:MM:SS"
     */
    public static function placeholderFecha(): string
    {
        $fmt = self::formatoFecha();
        $map = [
            'd' => 'dd',
            'j' => 'd',
            'm' => 'mm',
            'n' => 'm',
            'Y' => 'aaaa',
            'y' => 'aa',
            'H' => 'HH',
            'h' => 'hh',
            'i' => 'MM',
            's' => 'SS',
        ];
        $out = '';
        $len = strlen($fmt);
        for ($i = 0; $i < $len; $i++) {
            $ch = $fmt[$i];
            $out .= $map[$ch] ?? $ch;
        }
        return $out;
    }

    /**
     * Normaliza entradas HTML5 datetime-local ("YYYY-MM-DDTHH:MM" o "...:SS") a un string parseable.
     */
    public static function normalizeDatetimeLocal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        // 2026-02-17T19:30 -> 2026-02-17 19:30:00
        if (str_contains($v, 'T')) {
            $v = str_replace('T', ' ', $v);
        }
        // Si viene sin segundos, agregarlos
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $v)) {
            $v .= ':00';
        }
        return $v;
    }

    /**
     * Intenta parsear una fecha con hora usando el formato configurado.
     */
    public static function parseFecha($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = self::normalizeDatetimeLocal($value);
        if ($value === null || $value === '') {
            return null;
        }

        $formats = array_values(array_unique([
            self::formatoFecha(),
            // formatos comunes
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
        ]));

        foreach ($formats as $f) {
            try {
                $c = Carbon::createFromFormat($f, $value);
                if ($c !== false) {
                    return $c;
                }
            } catch (\Throwable $e) {
                // try next
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Intenta parsear una fecha (sin hora) usando el formato configurado.
     * Devuelve Carbon o null.
     */
    public static function parseFechaSolo($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $formats = array_values(array_unique([
            self::formatoFechaSolo(),
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
        ]));

        foreach ($formats as $f) {
            try {
                $c = Carbon::createFromFormat($f, $value);
                if ($c !== false) {
                    return $c;
                }
            } catch (\Throwable $e) {
                // try next
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function logoUrl(): ?string
    {
        $val = trim((string) self::valor('LOGO_SISTEMA', ''));
        if ($val === '') {
            return null;
        }

        // Normalizar valores antiguos que guardaban una URL tipo: .../public/{ID}
        // (p.ej. http://localhost/proyecto/public/0000000017)
        // En ese caso, el ID real es el último segmento.
        if (preg_match('#/public/(\d+)$#', $val, $m)) {
            $val = $m[1];
        }

        // Si el valor es un ID de ad_archivo_digital (dual: route o base64)
        // Nota: no dependemos estrictamente de Schema::hasTable para poder generar la ruta
        // incluso si la conexión falla momentáneamente durante el login.
        if (ctype_digit($val)) {
            try {
                $archivo = null;
                if (Schema::hasTable('ad_archivo_digital')) {
                    $archivo = AdArchivoDigital::where('id', $val)->first();
                }

                // Si existe el registro, intentamos base64 (si está activo).
                if ($archivo) {
                    // Modo base64 (Data URI)
                    if (self::loginImageMode() === 'base64') {
                        try {
                            if (!empty($archivo->digital)) {
                                // Soporta digital cifrado (nuevo) y base64 plano (antiguo)
                                $b64 = $archivo->digital;
                                try {
                                    $b64 = Crypt::decryptString($archivo->digital);
                                } catch (\Throwable $e) {
                                    // si no está cifrado, asumimos base64 plano
                                }

                                // Si viene como data:image/...;base64,...
                                if (is_string($b64) && str_starts_with($b64, 'data:')) {
                                    return $b64;
                                }

                                $binary = base64_decode((string) $b64, true);
                                if ($binary !== false) {
                                    $mime = $archivo->tipo_mime ?: 'image/png';
                                    return 'data:' . $mime . ';base64,' . base64_encode($binary);
                                }
                            }
                        } catch (\Throwable $e) {
                            // si falla base64, caemos a route
                        }
                    }

                    // Modo route (default): Ruta pública (el login no está autenticado)
                    return route('ArchivosDigitalesPublico', ['id' => $archivo->id]);
                }

                // Si no pudimos leer de DB (o no existe), igual devolvemos la ruta pública
                // para que el controlador intente servirlo.
                return route('ArchivosDigitalesPublico', ['id' => $val]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Si ya es URL absoluta, devolver tal cual
        if (preg_match('#^https?://#i', $val)) {
            return $val;
        }

        // Ruta relativa en /public
        return asset($val);
    }

    /**
     * Devuelve una URL pública a partir de una clave de configuración que almacena
     * una ruta relativa en /public o una URL absoluta.
     */
    public static function publicAssetUrl(string $clave): ?string
    {
        $path = trim((string) self::valor($clave, ''));
        if ($path === '') {
            return null;
        }

        // Normalizar valores antiguos que guardaban una URL tipo: .../public/{ID}
        if (preg_match('#/public/(\d+)$#', $path, $m)) {
            $path = $m[1];
        }

        // Si el valor es un ID de ad_archivo_digital, servirlo por la ruta pública
        if (ctype_digit($path)) {
            try {
                if (Schema::hasTable('ad_archivo_digital')) {
                    $archivo = AdArchivoDigital::where('id', $path)->first();
                    if ($archivo) {
                        // Ruta pública (el login no está autenticado)
                        return route('ArchivosDigitalesPublico', ['id' => $archivo->id]);
                    }
                }

                // Si no pudimos leer de DB, igual devolvemos la ruta pública.
                return route('ArchivosDigitalesPublico', ['id' => $path]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return asset($path);
    }

    public static function loginIllusLeftUrl(): ?string
    {
        return self::loginImageSrc('LOGIN_ILLUS_LEFT', 'uploads/login/default_left.png');
    }

    public static function loginIllusRightUrl(): ?string
    {
        return self::loginImageSrc('LOGIN_ILLUS_RIGHT', 'uploads/login/default_right.png');
    }

    /**
     * Modo de carga de imágenes del login.
     * Valores soportados:
     * - route  : sirve la imagen por una ruta pública (/admin/public-file/{id})
     * - base64 : incrusta la imagen como Data URI (data:image/...;base64,...)
     */
    public static function loginImageMode(): string
    {
        // Por defecto usamos base64 para evitar 404 cuando el login se sirve desde /public
        // o cuando el APP_URL no coincide con el path real.
        $mode = strtolower(trim((string) self::valor('LOGIN_IMAGE_MODE', 'base64')));
        return in_array($mode, ['route', 'base64'], true) ? $mode : 'route';
    }

    /**
     * Devuelve el "src" para una imagen del login (dual: route o base64),
     * con fallback a un archivo en /public.
     */
    public static function loginImageSrc(string $configKey, string $fallbackPublicPath): ?string
    {
        $val = trim((string) self::valor($configKey, ''));

        // Normalizar valores antiguos que guardaban una URL tipo: .../public/{ID}
        if (preg_match('#/public/(\d+)$#', $val, $m)) {
            $val = $m[1];
        }

        // 1) Si es un ID de ad_archivo_digital
        if ($val !== '' && ctype_digit($val)) {
            try {
                $archivo = null;
                if (Schema::hasTable('ad_archivo_digital')) {
                    $archivo = AdArchivoDigital::where('id', $val)->first();
                }

                if ($archivo) {
                    // Modo base64
                    if (self::loginImageMode() === 'base64') {
                        if (!empty($archivo->digital)) {
                            $b64 = $archivo->digital;
                            try {
                                $b64 = Crypt::decryptString($archivo->digital);
                            } catch (\Throwable $e) {
                                // si no está cifrado, asumimos base64 plano
                            }

                            // Si viene como data URI completo, devolver tal cual
                            if (is_string($b64) && str_starts_with($b64, 'data:')) {
                                return $b64;
                            }

                            $binary = base64_decode((string) $b64, true);
                            if ($binary !== false) {
                                $mime = $archivo->tipo_mime ?: 'image/png';
                                return 'data:' . $mime . ';base64,' . base64_encode($binary);
                            }
                        }
                        // si falla base64, caemos a route
                    }

                    // Modo route (default)
                    return route('ArchivosDigitalesPublico', ['id' => $archivo->id]);
                }

                // Si no pudimos leer de DB (o no existe), igual devolvemos la ruta pública.
                return route('ArchivosDigitalesPublico', ['id' => $val]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 2) Si es URL absoluta
        if ($val !== '' && preg_match('#^https?://#i', $val)) {
            return $val;
        }

        // 3) Si es ruta relativa en /public
        if ($val !== '') {
            return asset($val);
        }

        // 4) Fallback
        return $fallbackPublicPath !== '' ? asset($fallbackPublicPath) : null;
    }

    /**
     * ID del archivo (ad_archivo_digital) configurado como logo de reportes.
     *
     * Nota: se guarda como clave `logo_reportes` en pg_configuraciones.
     */
    public static function reportLogoArchivoId(): ?string
    {
        $id = trim((string) self::valor('logo_reportes', ''));
        return $id !== '' ? $id : null;
    }

    /**
     * URL del logo para reportes.
     * - Prioriza `logo_reportes` (ad_archivo_digital)
     * - Fallback: `LOGO_SISTEMA` (ruta en public)
     */
    public static function reportLogoUrl(): ?string
    {
        $id = self::reportLogoArchivoId();
        if ($id) {
            try {
                return route('ArchivosDigitalesPublico', ['id' => $id]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return self::logoUrl();
    }

    /**
     * Devuelve un Data URI (base64) del logo para reportes (para incrustar en PDF).
     * - Prioriza `logo_reportes` (ad_archivo_digital)
     * - Fallback: `LOGO_SISTEMA` (ruta en public)
     */
    public static function reportLogoDataUri(): ?string
    {
        // 1) logo_reportes desde ad_archivo_digital
        $id = self::reportLogoArchivoId();
        if ($id && Schema::hasTable('ad_archivo_digital')) {
            try {
                $archivo = AdArchivoDigital::where('id', $id)->first();
                if ($archivo && !empty($archivo->digital)) {
                    $b64 = Crypt::decryptString($archivo->digital);
                    $binary = base64_decode($b64, true);
                    if ($binary !== false) {
                        $mime = $archivo->tipo_mime ?: 'image/png';
                        return 'data:' . $mime . ';base64,' . base64_encode($binary);
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 2) fallback LOGO_SISTEMA desde public
        $path = trim((string) self::valor('LOGO_SISTEMA', ''));
        if ($path !== '') {
            try {
                if (!preg_match('#^https?://#i', $path)) {
                    $full = public_path($path);
                    if (is_file($full)) {
                        $binary = @file_get_contents($full);
                        if ($binary !== false) {
                            $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
                            $mime = 'image/png';
                            if (in_array($ext, ['jpg', 'jpeg'], true)) $mime = 'image/jpeg';
                            elseif ($ext === 'webp') $mime = 'image/webp';
                            elseif ($ext === 'gif') $mime = 'image/gif';
                            return 'data:' . $mime . ';base64,' . base64_encode($binary);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return null;
    }
}
