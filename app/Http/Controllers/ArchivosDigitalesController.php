<?php

namespace App\Http\Controllers;

use App\Models\AdArchivoDigital;
use App\Models\AdTipoArchivo;
use App\Models\AdTipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ArchivosDigitalesController extends Controller
{
    private function normalizeExt($ext): string
    {
        $ext = strtolower(trim((string) $ext));
        $ext = ltrim($ext, '.');
        return $ext;
    }

    private function normalizeMime($mime): string
    {
        $mime = strtolower(trim((string) $mime));
        // Alias comunes
        if ($mime === 'image/jpg') {
            $mime = 'image/jpeg';
        }
        return $mime;
    }

    private function mimeMatchesCatalog(?string $catalogMime, ?string $uploadedMime): bool
    {
        $catalogMime = $this->normalizeMime($catalogMime ?? '');
        $uploadedMime = $this->normalizeMime($uploadedMime ?? '');

        if ($catalogMime === '' || $uploadedMime === '') {
            return true;
        }

        // XML suele venir como application/xml o text/xml
        if (str_contains($catalogMime, 'xml') && str_contains($uploadedMime, 'xml')) {
            return true;
        }

        // JPEG suele venir como image/jpeg, en el catálogo podría estar image/jpg
        if ($catalogMime === 'image/jpeg' && $uploadedMime === 'image/jpeg') {
            return true;
        }

        return $catalogMime === $uploadedMime;
    }

    public function __construct()
    {
        parent::__construct();
    }

    private function findArchivoById(string $id): ?AdArchivoDigital
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        $archivo = AdArchivoDigital::where('id', $id)->first();
        if ($archivo) {
            return $archivo;
        }

        try {
            $archivoAlt = (new AdArchivoDigital())->setConnection('mysql_archivos');
            return $archivoAlt->newQuery()->where('id', $id)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function Index(Request $request)
    {
        // Symfony 7.4+ deprecates Request::get(), use query/request bags explicitly.
        $soloEliminados = $request->query('eliminados') == 1;

        $query = AdArchivoDigital::with(['tipoDocumento', 'tipoArchivo'])
            ->orderByDesc('id');

        $archivos = $soloEliminados
            ? $query->eliminados()->paginate(20)
            : $query->activos()->paginate(20);

        return view('ArchivosDigitales.index', [
            'archivos' => $archivos,
            'soloEliminados' => $soloEliminados,
        ]);
    }

    public function Create()
    {
        return view('ArchivosDigitales.create', [
            'tiposDocumento' => AdTipoDocumento::orderBy('descripcion')->get(),
            'tiposArchivo' => AdTipoArchivo::orderBy('descripcion')->get(),
        ]);
    }

    public function Store(Request $request)
    {
        $request->validate([
            // max en Laravel es KB. Se deja alto (50MB) y luego se valida por tipo_documento.
            'archivo' => ['required', 'file', 'max:51200'],
            'tipo_documento_codigo' => ['nullable', 'string', 'max:5'],
            'tipo_archivo_codigo' => ['nullable', 'string', 'max:5'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $tipoDocumento = null;
        if ($request->filled('tipo_documento_codigo')) {
            $tipoDocumento = AdTipoDocumento::where('codigo', $request->tipo_documento_codigo)->first();
        }

        if ($tipoDocumento && is_numeric($tipoDocumento->tamano_maximo)) {
            // tamano_maximo en KB (según tu tabla)
            $maxBytes = ((int)$tipoDocumento->tamano_maximo) * 1024;
            if ($request->file('archivo')->getSize() > $maxBytes) {
                return back()->withInput()->withErrors([
                    'archivo' => 'El archivo supera el tamaño máximo permitido para el tipo de documento seleccionado ('.$tipoDocumento->tamano_maximo.' KB).',
                ]);
            }
        }

        $tipoArchivo = null;
        if ($request->filled('tipo_archivo_codigo')) {
            $tipoArchivo = AdTipoArchivo::where('codigo', $request->tipo_archivo_codigo)->first();
        }

        $file = $request->file('archivo');
        $ext = $this->normalizeExt($file->getClientOriginalExtension());
        $mime = $file->getClientMimeType();

        // Validación opcional contra el catálogo (si existe)
        if ($tipoArchivo) {
            $catalogExt = $this->normalizeExt($tipoArchivo->extension);
            if ($catalogExt !== '' && $catalogExt !== $ext) {
                return back()->withInput()->withErrors([
                    'archivo' => 'La extensión del archivo no coincide con la extensión configurada en el tipo de archivo seleccionado ('.$tipoArchivo->extension.').',
                ]);
            }
            if (!empty($tipoArchivo->tipo_mime) && !$this->mimeMatchesCatalog((string)$tipoArchivo->tipo_mime, (string)$mime)) {
                return back()->withInput()->withErrors([
                    'archivo' => 'El MIME del archivo no coincide con el MIME configurado en el tipo de archivo seleccionado ('.$tipoArchivo->tipo_mime.').',
                ]);
            }
        }

        // Guardar el archivo EN BASE DE DATOS cifrado (no se guarda en carpetas)
        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false) {
            return back()->withInput()->withErrors([
                'archivo' => 'No se pudo leer el archivo subido.',
            ]);
        }

        // Nota: Crypt trabaja con strings, por eso se cifra el base64.
        $encrypted = Crypt::encryptString(base64_encode($binary));

        $archivo = new AdArchivoDigital();
        $archivo->tipo_documento_codigo = $request->filled('tipo_documento_codigo') ? $request->tipo_documento_codigo : null;
        $archivo->tipo_archivo_codigo = $request->filled('tipo_archivo_codigo') ? $request->tipo_archivo_codigo : null;
        $archivo->nombre_original = $file->getClientOriginalName();
        // Se deja ruta vacío por compatibilidad con la estructura anterior
        $archivo->ruta = '';
        $archivo->digital = $encrypted;
        $archivo->tipo_mime = $mime;
        $archivo->extension = $ext;
        $archivo->tamano = (int) $file->getSize();
        $archivo->descripcion = $request->filled('descripcion') ? strip_tags($request->descripcion) : null;
        $archivo->estado = null;
        $archivo->save();

        return redirect()->route('ArchivosDigitalesIndex')->with('success', 'Archivo cargado correctamente.');
    }

    public function Ver($id)
    {
        $archivo = $this->findArchivoById((string) $id);
        if (!$archivo) {
            abort(404);
        }

        // Si ya está en DB, se descifra y se sirve
        if (!empty($archivo->digital)) {
            try {
                // Soporta "digital" cifrado (nuevo) y base64 plano/data-uri (legado)
                $b64 = $archivo->digital;
                try {
                    $b64 = Crypt::decryptString($archivo->digital);
                } catch (\Throwable $e) {
                    // si no está cifrado, asumimos base64 plano o data-uri
                }

                if (is_string($b64) && str_starts_with($b64, 'data:')) {
                    // data:image/...;base64,XXXX
                    [, $payload] = array_pad(explode(',', $b64, 2), 2, '');
                    $b64 = $payload;
                }

                $binary = base64_decode((string) $b64, true);
                if ($binary === false) {
                    abort(404);
                }
            } catch (\Throwable $e) {
                abort(404);
            }

            return response($binary, 200)
                ->header('Content-Type', $archivo->tipo_mime ?? 'application/octet-stream')
                ->header('Content-Disposition', 'inline; filename="'.$archivo->nombre_original.'"');
        }

        // Fallback: si existe físicamente (registros antiguos), servirlo y migrarlo a DB
        if (!empty($archivo->ruta)) {
            $path = public_path($archivo->ruta);
            if (is_file($path)) {
                $binary = @file_get_contents($path);
                if ($binary !== false) {
                    try {
                        $archivo->digital = Crypt::encryptString(base64_encode($binary));
                        $archivo->ruta = '';
                        $archivo->save();

                        // Opcional: eliminar el físico para cumplir “no guardar en carpetas”
                        @unlink($path);
                    } catch (\Throwable $e) {
                        // si falla la migración, igual se sirve desde disco
                    }

                    return response($binary, 200)
                        ->header('Content-Type', $archivo->tipo_mime ?? 'application/octet-stream')
                        ->header('Content-Disposition', 'inline; filename="'.$archivo->nombre_original.'"');
                }
            }
        }

        abort(404);
    }

    /**
     * Versión pública del visor (sin middleware auth).
     *
     * Se usa para servir imágenes del login (logo/ilustraciones) que se almacenan
     * en ad_archivo_digital y se referencian desde pg_configuraciones.
     */
    public function PublicVer($id)
    {
        return $this->Ver($id);
    }

    public function Edit($id)
    {
        $archivo = $this->findArchivoById((string) $id);
        if (!$archivo) {
            abort(404);
        }

        return view('ArchivosDigitales.edit', [
            'archivo' => $archivo,
            'tiposDocumento' => AdTipoDocumento::orderBy('descripcion')->get(),
            'tiposArchivo' => AdTipoArchivo::orderBy('descripcion')->get(),
        ]);
    }

    public function Update(Request $request, $id)
    {
        $archivo = $this->findArchivoById((string) $id);
        if (!$archivo) {
            abort(404);
        }

        $request->validate([
            'archivo' => ['nullable', 'file', 'max:51200'],
            'tipo_documento_codigo' => ['nullable', 'string', 'max:5'],
            'tipo_archivo_codigo' => ['nullable', 'string', 'max:5'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $archivo->tipo_documento_codigo = $request->filled('tipo_documento_codigo') ? $request->tipo_documento_codigo : null;
        $archivo->tipo_archivo_codigo = $request->filled('tipo_archivo_codigo') ? $request->tipo_archivo_codigo : null;
        $archivo->descripcion = $request->filled('descripcion') ? strip_tags($request->descripcion) : null;

        $tipoDocumento = null;
        if ($request->filled('tipo_documento_codigo')) {
            $tipoDocumento = AdTipoDocumento::where('codigo', $request->tipo_documento_codigo)->first();
        }

        $tipoArchivo = null;
        if ($request->filled('tipo_archivo_codigo')) {
            $tipoArchivo = AdTipoArchivo::where('codigo', $request->tipo_archivo_codigo)->first();
        }

        // Validar cuando se EDITA el tipo de archivo SIN reemplazar el archivo.
        // (Evita que se asigne "PDF" a un archivo que no es PDF, etc.)
        if (!$request->hasFile('archivo') && $tipoArchivo) {
            $extActual = $this->normalizeExt($archivo->extension);
            $mimeActual = $this->normalizeMime($archivo->tipo_mime);

            $catalogExt = $this->normalizeExt($tipoArchivo->extension);
            if ($catalogExt !== '' && $extActual !== '' && $catalogExt !== $extActual) {
                return back()->withInput()->withErrors([
                    'tipo_archivo_codigo' => 'El tipo de archivo seleccionado no coincide con la extensión actual (.' . $extActual . ').',
                ]);
            }
            if (!empty($tipoArchivo->tipo_mime) && $mimeActual !== '' && !$this->mimeMatchesCatalog((string)$tipoArchivo->tipo_mime, (string)$mimeActual)) {
                return back()->withInput()->withErrors([
                    'tipo_archivo_codigo' => 'El tipo de archivo seleccionado no coincide con el MIME actual (' . $mimeActual . ').',
                ]);
            }
        }

        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $ext = $this->normalizeExt($file->getClientOriginalExtension());
            $mime = $file->getClientMimeType();

            if ($tipoDocumento && is_numeric($tipoDocumento->tamano_maximo)) {
                $maxBytes = ((int)$tipoDocumento->tamano_maximo) * 1024;
                if ($file->getSize() > $maxBytes) {
                    return back()->withInput()->withErrors([
                        'archivo' => 'El archivo supera el tamaño máximo permitido para el tipo de documento seleccionado ('.$tipoDocumento->tamano_maximo.' KB).',
                    ]);
                }
            }

            if ($tipoArchivo) {
                $catalogExt = $this->normalizeExt($tipoArchivo->extension);
                if ($catalogExt !== '' && $catalogExt !== $ext) {
                    return back()->withInput()->withErrors([
                        'archivo' => 'La extensión del archivo no coincide con la extensión configurada en el tipo de archivo seleccionado ('.$tipoArchivo->extension.').',
                    ]);
                }
                if (!empty($tipoArchivo->tipo_mime) && !$this->mimeMatchesCatalog((string)$tipoArchivo->tipo_mime, (string)$mime)) {
                    return back()->withInput()->withErrors([
                        'archivo' => 'El MIME del archivo no coincide con el MIME configurado en el tipo de archivo seleccionado ('.$tipoArchivo->tipo_mime.').',
                    ]);
                }
            }

            $binary = @file_get_contents($file->getRealPath());
            if ($binary === false) {
                return back()->withInput()->withErrors([
                    'archivo' => 'No se pudo leer el archivo subido.',
                ]);
            }

            $encrypted = Crypt::encryptString(base64_encode($binary));

            $archivo->nombre_original = $file->getClientOriginalName();
            $archivo->ruta = '';
            $archivo->digital = $encrypted;
            $archivo->tipo_mime = $mime;
            $archivo->extension = $ext;
            $archivo->tamano = (int) $file->getSize();
        }

        $archivo->save();

        return redirect()->route('ArchivosDigitalesIndex')->with('success', 'Archivo actualizado correctamente.');
    }

    public function Delete(Request $request, $id)
    {
        $archivo = $this->findArchivoById((string) $id);
        if (!$archivo) {
            abort(404);
        }
        $archivo->estado = 'X';
        $archivo->save();

        return redirect()->route('ArchivosDigitalesIndex')->with('success', 'Archivo eliminado (lógico) correctamente.');
    }
}
