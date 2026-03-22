<?php

namespace App\Http\Controllers;

use App\Models\Idioma;
use App\Models\AdArchivoDigital;
use App\Models\PgConfiguracion;
use App\Models\PgGeneralTraduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class LoginSettingsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $idiomas = collect();
        try {
            if (Schema::hasTable('pg_idiomas')) {
                $idiomas = Idioma::query()->where('activo', 1)->orderBy('por_defecto', 'desc')->orderBy('nombre')->get();
            }
        } catch (\Throwable $e) {
            $idiomas = collect();
        }

        if ($idiomas->isEmpty()) {
            // fallback mínimo
            $idiomas = collect([(object)['codigo' => 'es', 'nombre' => 'Español'], (object)['codigo' => 'en', 'nombre' => 'English']]);
        }

        // Asegurar defaults en ES
        pg_t('login.about.body', '<p>Este sistema permite acceder al panel de control y gestionar los módulos autorizados.</p>');
        pg_t('login.terms.body', '<p>Al acceder y usar este sitio, aceptas los términos y condiciones definidos por la organización.</p>');
        pg_t('login.privacy.body', '<p>Tu información se trata conforme a nuestra política de privacidad.</p>');

        $keys = ['login.about.body', 'login.terms.body', 'login.privacy.body'];
        $texts = [];
        foreach ($idiomas as $i) {
            foreach ($keys as $k) {
                $row = null;
                try {
                    $row = PgGeneralTraduccion::query()
                        ->where('clave', $k)
                        ->where('idioma_codigo', $i->codigo)
                        ->whereNull('estado')
                        ->first();
                } catch (\Throwable $e) {
                    $row = null;
                }
                $texts[$i->codigo][$k] = $row ? (string)$row->texto : '';
            }
        }

        return view('LoginSettings.index', [
            'idiomas' => $idiomas,
            'texts' => $texts,
            'loginImageMode' => PgConfiguracion::loginImageMode(),
            'loginLeft' => PgConfiguracion::loginIllusLeftUrl(),
            'loginRight' => PgConfiguracion::loginIllusRightUrl(),
            'logoUrl' => PgConfiguracion::logoUrl(),
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'texts' => 'array',
            'login_image_mode' => 'nullable|string',
        ];
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        }

        // Imágenes
        $this->handleUpload($request, 'login_illus_left', 'LOGIN_ILLUS_LEFT', 'Login: Ilustración izquierda');
        $this->handleUpload($request, 'login_illus_right', 'LOGIN_ILLUS_RIGHT', 'Login: Ilustración derecha');
        $this->handleUpload($request, 'logo_sistema', 'LOGO_SISTEMA', 'Logo del sistema');

        // Modo de carga de imágenes del login (dual): route | base64
        $mode = strtolower(trim((string) $request->input('login_image_mode', 'route')));
        if (!in_array($mode, ['route', 'base64'], true)) {
            $mode = 'route';
        }
        PgConfiguracion::setValor('LOGIN_IMAGE_MODE', $mode, 'texto', 'Modo de carga de imágenes del login (route|base64)', 'apariencia');

        // Textos por idioma (HTML permitido)
        $texts = (array)$request->input('texts', []);
        $allowedKeys = ['login.about.body', 'login.terms.body', 'login.privacy.body'];

        foreach ($texts as $lang => $arr) {
            if (!is_array($arr)) continue;
            foreach ($allowedKeys as $key) {
                if (!array_key_exists($key, $arr)) continue;
                $html = (string)$arr[$key];
                PgGeneralTraduccion::query()->updateOrCreate(
                    ['clave' => $key, 'idioma_codigo' => $lang, 'estado' => null],
                    ['texto' => $html]
                );
                Cache::forget("pg_tr:$lang:$key");
            }
        }

        PgConfiguracion::clearCache();

        return redirect()->route('login-settings')->with('success', 'Configuración de login guardada correctamente.');
    }

    /**
     * Sube una imagen y la guarda en ad_archivo_digital (BBDD, cifrada),
     * y registra el ID en pg_configuraciones.
     */
    private function handleUpload(Request $request, string $inputName, string $configKey, ?string $descripcion = null): void
    {
        if (!$request->hasFile($inputName)) {
            return;
        }
        $file = $request->file($inputName);
        if (!$file || !$file->isValid()) {
            return;
        }

        // Leer binario
        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false) {
            return;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $mime = $file->getClientMimeType();
        $encrypted = Crypt::encryptString(base64_encode($binary));

        $archivo = new AdArchivoDigital();
        $archivo->tipo_documento_codigo = null;
        $archivo->tipo_archivo_codigo = null;
        $archivo->nombre_original = $file->getClientOriginalName();
        $archivo->ruta = '';
        $archivo->digital = $encrypted;
        $archivo->tipo_mime = $mime;
        $archivo->extension = $ext;
        $archivo->tamano = (int) $file->getSize();
        $archivo->descripcion = $descripcion ?: ('Login: ' . $configKey);
        $archivo->estado = null;
        $archivo->save();

        // Guardar ID en la configuración
        PgConfiguracion::setValor($configKey, $archivo->id, 'archivo', $archivo->descripcion, 'apariencia');
    }
}
