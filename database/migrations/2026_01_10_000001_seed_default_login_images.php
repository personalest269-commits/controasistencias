<?php

use App\Models\AdArchivoDigital;
use App\Models\PgConfiguracion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function seedOne(string $configKey, string $descripcion, string $publicPath): void
    {
        // Si ya hay valor configurado, no tocarlo.
        $current = trim((string) PgConfiguracion::valor($configKey, ''));
        if ($current !== '') {
            return;
        }

        $full = public_path($publicPath);
        if (!is_file($full)) {
            return;
        }

        $binary = @file_get_contents($full);
        if ($binary === false) {
            return;
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION) ?: 'png');
        $mime = 'image/png';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($full);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }

        $archivo = new AdArchivoDigital();
        $archivo->tipo_documento_codigo = null;
        $archivo->tipo_archivo_codigo = null;
        $archivo->nombre_original = basename($full);
        $archivo->ruta = '';
        $archivo->digital = Crypt::encryptString(base64_encode($binary));
        $archivo->tipo_mime = $mime;
        $archivo->extension = $ext;
        $archivo->tamano = strlen($binary);
        $archivo->descripcion = $descripcion;
        $archivo->estado = null;
        $archivo->save();

        PgConfiguracion::setValor($configKey, $archivo->id, 'archivo', $descripcion, 'apariencia');
    }

    public function up(): void
    {
        if (!Schema::hasTable('pg_configuraciones') || !Schema::hasTable('ad_archivo_digital')) {
            return;
        }

        // Imágenes “por defecto” como en tu captura.
        $this->seedOne('LOGIN_ILLUS_LEFT', 'Login: Ilustración izquierda (default)', 'uploads/login/default_left.png');
        $this->seedOne('LOGIN_ILLUS_RIGHT', 'Login: Ilustración derecha (default)', 'uploads/login/default_right.png');
    }

    public function down(): void
    {
        // No se elimina, porque podrían estar en uso.
    }
};
