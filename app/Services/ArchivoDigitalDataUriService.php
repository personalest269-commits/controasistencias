<?php

namespace App\Services;

use App\Models\AdArchivoDigital;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ArchivoDigitalDataUriService
{
    /**
     * Devuelve data-uri para imágenes guardadas en ad_archivo_digital.
     * Retorna null si no existe o no es imagen.
     */
    public static function imageDataUri(?string $id): ?string
    {
        if (!$id || trim($id) === '') {
            return null;
        }

        try {
            $archivo = AdArchivoDigital::where('id', $id)->first();
            if (!$archivo || !empty($archivo->estado)) {
                return null;
            }
            $mime = (string) ($archivo->tipo_mime ?? 'application/octet-stream');
            if (stripos($mime, 'image/') !== 0) {
                return null;
            }

            $binary = null;
            if (!empty($archivo->digital)) {
                $b64 = Crypt::decryptString($archivo->digital);
                $binary = base64_decode($b64, true);
            } elseif (!empty($archivo->ruta)) {
                $path = public_path($archivo->ruta);
                if (is_file($path)) {
                    $binary = @file_get_contents($path);
                }
            }

            if ($binary === false || $binary === null) {
                return null;
            }

            // Evitar data-uri gigantes (10MB)
            if (strlen($binary) > (10 * 1024 * 1024)) {
                return null;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        } catch (\Throwable $e) {
            Log::debug('No se pudo generar data-uri', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
