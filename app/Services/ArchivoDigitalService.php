<?php

namespace App\Services;

use App\Models\AdArchivoDigital;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;

/**
 * Guarda archivos (fotos/documentos) dentro de ad_archivo_digital.
 *
 * - El binario se guarda cifrado en ad_archivo_digital.digital.
 * - "ruta" queda vacío (el sistema consume desde DB).
 */
class ArchivoDigitalService
{
    /**
     * @return string|null id del archivo guardado
     */
    public static function store(
        UploadedFile $file,
        ?string $descripcion = null,
        ?string $tipoDocumento = null,
        ?string $tipoArchivo = null,
        ?string $connection = null
    ): ?string {
        try {
            if (!$file->isValid()) {
                \Log::warning('Archivo inválido al guardar en ad_archivo_digital', [
                    'error_code' => $file->getError(),
                    'error_message' => $file->getErrorMessage(),
                    'original_name' => $file->getClientOriginalName(),
                ]);
                return null;
            }

            $binary = @file_get_contents($file->getRealPath());
            if ($binary === false) {
                \Log::warning('No se pudo leer el temporal del archivo para ad_archivo_digital', [
                    'original_name' => $file->getClientOriginalName(),
                    'tmp_path' => $file->getRealPath(),
                ]);
                return null;
            }

            $encrypted = Crypt::encryptString(base64_encode($binary));

            $ext = strtolower(trim((string) $file->getClientOriginalExtension()));
            $ext = ltrim($ext, '.');
            if ($ext === '') {
                $ext = 'bin';
            }

            $mime = $file->getClientMimeType() ?: 'application/octet-stream';
            $tipoDocumentoCodigo = $tipoDocumento;
            if ($tipoDocumentoCodigo === null && str_starts_with(strtolower($mime), 'image/')) {
                // Convención del catálogo ad_tipo_documento:
                // 00001 = FOTOGRAFÍA (según datos semilla / ambientes productivos).
                // Esto evita rechazos por validaciones/trigger que esperan tipo_documento para imágenes.
                $tipoDocumentoCodigo = '00001';
            }

            $archivo = new AdArchivoDigital();
            if ($connection && trim($connection) !== '') {
                $archivo->setConnection($connection);
            }

            $archivo->tipo_documento_codigo = $tipoDocumento;
            $archivo->tipo_archivo_codigo = $tipoArchivo;
            $archivo->nombre_original = $file->getClientOriginalName();
            $archivo->ruta = '';
            $archivo->digital = $encrypted;
            $archivo->tipo_mime = $mime;
            $archivo->extension = $ext;
            $archivo->tamano = (int) $file->getSize();
            $archivo->descripcion = $descripcion;
            $archivo->estado = null;
            $archivo->save();

            return (string) $archivo->id;
        } catch (\Throwable $e) {
            \Log::warning('No se pudo guardar archivo en ad_archivo_digital', [
                'error' => $e->getMessage(),
                'connection' => $connection,
            ]);
            return null;
        }
    }
}
