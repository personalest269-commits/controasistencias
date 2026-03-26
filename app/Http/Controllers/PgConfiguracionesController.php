<?php

namespace App\Http\Controllers;

use App\Models\PgConfiguracion;
use App\Services\ArchivoDigitalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PgConfiguracionesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function Index()
    {
        $configs = PgConfiguracion::query()
            ->whereNull('estado')
            ->orderBy('grupo')
            ->orderBy('clave')
            ->get();

        return view('PgConfiguraciones.index', [
            'configs' => $configs,
            'logoUrl' => PgConfiguracion::logoUrl(),
            'logoReportesUrl' => PgConfiguracion::reportLogoUrl(),
            'loginLeftUrl' => PgConfiguracion::loginIllusLeftUrl(),
            'loginRightUrl' => PgConfiguracion::loginIllusRightUrl(),
        ]);
    }

    public function Update(Request $request)
    {
        // Campos simples
        $rules = [
            'configs.APP_TIMEZONE' => 'nullable|string|max:120',
            'configs.FORMATO_FECHA' => 'nullable|string|max:50',
            'configs.NOMBRE_SISTEMA' => 'nullable|string|max:120',
            'configs.ITEMS_POR_PAGINA' => 'nullable|integer|min:1|max:500',
            'configs.LOGIN_TEMPLATE' => 'nullable|string|max:30',
            'configs.ASISTENCIA_MODO_REGISTRO' => 'nullable|in:single_check,dual_check',

            // Seguridad (reCAPTCHA)
            'configs.RECAPTCHA_SITE_KEY' => 'nullable|string|max:255',
            'configs.RECAPTCHA_SECRET_KEY' => 'nullable|string|max:255',
        ];

        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        }

        $configs = (array) $request->input('configs', []);

        // Booleans: si no llegan (checkbox desmarcado) => N
        $boolKeys = ['CORREO_ACTIVO', 'REGISTRO_USUARIO_ACTIVO', 'FRONTEND_ACTIVO', 'UI_LINK_FRONTEND_ACTIVO', 'UI_SWITCH_TEMPLATE_ACTIVO'];
        foreach ($boolKeys as $k) {
            $configs[$k] = isset($configs[$k]) ? 'S' : 'N';
        }

        // Upsert de cada config
        PgConfiguracion::setValor('NOMBRE_SISTEMA', $configs['NOMBRE_SISTEMA'] ?? null, 'texto', 'Nombre visible del sistema', 'general');
        PgConfiguracion::setValor('APP_TIMEZONE', $configs['APP_TIMEZONE'] ?? null, 'texto', 'Zona horaria del sistema (PHP/Laravel)', 'general');
        PgConfiguracion::setValor('FORMATO_FECHA', $configs['FORMATO_FECHA'] ?? null, 'texto', 'Formato para mostrar fechas (Carbon/PHP date format)', 'general');
        PgConfiguracion::setValor('ITEMS_POR_PAGINA', $configs['ITEMS_POR_PAGINA'] ?? '30', 'numero', 'Cantidad de registros por página en listados', 'general');
        PgConfiguracion::setValor(
            'ASISTENCIA_MODO_REGISTRO',
            $configs['ASISTENCIA_MODO_REGISTRO'] ?? 'single_check',
            'texto',
            'Modo de validación de asistencia: single_check (1 check) o dual_check (inicio+fin).',
            'asistencia'
        );

        PgConfiguracion::setValor('CORREO_ACTIVO', $configs['CORREO_ACTIVO'] ?? 'S', 'booleano', 'Habilitar envío de correos del sistema', 'correo');
        PgConfiguracion::setValor('REGISTRO_USUARIO_ACTIVO', $configs['REGISTRO_USUARIO_ACTIVO'] ?? 'S', 'booleano', 'Permitir registro de usuarios desde la pantalla de registro', 'seguridad');
        PgConfiguracion::setValor('FRONTEND_ACTIVO', $configs['FRONTEND_ACTIVO'] ?? 'S', 'booleano', 'Habilitar el frontend (sitio). Si está en N, redirige al login', 'seguridad');

        // reCAPTCHA: solo se considera "habilitado" si existen ambas llaves.
        // (En login se valida contra Google usando el secret.)
        PgConfiguracion::setValor('RECAPTCHA_SITE_KEY', $configs['RECAPTCHA_SITE_KEY'] ?? null, 'texto', 'reCAPTCHA v2: Site Key (si está vacío, NO se muestra captcha en login)', 'seguridad');
        PgConfiguracion::setValor('RECAPTCHA_SECRET_KEY', $configs['RECAPTCHA_SECRET_KEY'] ?? null, 'texto', 'reCAPTCHA v2: Secret Key (si está vacío, NO se valida captcha)', 'seguridad');
        $tpl = strtoupper(trim((string) ($configs['LOGIN_TEMPLATE'] ?? 'DEFAULT')));
        if (!in_array($tpl, ['DEFAULT', 'CONTROL'], true)) {
            $tpl = 'DEFAULT';
        }
        PgConfiguracion::setValor('LOGIN_TEMPLATE', $tpl, 'texto', 'Plantilla de pantalla de login (DEFAULT|CONTROL)', 'apariencia');

        // UI (barra superior): mostrar/ocultar link Frontend y selector de plantilla
        PgConfiguracion::setValor('UI_LINK_FRONTEND_ACTIVO', $configs['UI_LINK_FRONTEND_ACTIVO'] ?? 'S', 'booleano', 'UI: Mostrar link Frontend en barra superior', 'apariencia');
        PgConfiguracion::setValor('UI_SWITCH_TEMPLATE_ACTIVO', $configs['UI_SWITCH_TEMPLATE_ACTIVO'] ?? 'S', 'booleano', 'UI: Mostrar selector de plantilla (AdminLTE/Gentelella) en barra superior', 'apariencia');


        // Logo
        if ($request->hasFile('logo_sistema')) {
            $file = $request->file('logo_sistema');
            if ($file && $file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension() ?: '');
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    return redirect()->back()->withErrors([
                        'logo_sistema' => 'El logo debe ser una imagen (jpg/png/webp/gif).',
                    ])->withInput();
                }
                $idArchivo = ArchivoDigitalService::store($file, 'Logo del sistema');
                if (!$idArchivo) {
                    return redirect()->back()->withErrors([
                        'logo_sistema' => 'No se pudo guardar el logo en ad_archivo_digital.',
                    ])->withInput();
                }
                // Ahora LOGO_SISTEMA puede ser ID (ad_archivo_digital) o ruta; PgConfiguracion::logoUrl() soporta ambos.
                PgConfiguracion::setValor('LOGO_SISTEMA', $idArchivo, 'archivo', 'Logo del sistema (ad_archivo_digital)', 'apariencia');
            }
        }

        // Imágenes de login (ilustraciones) -> se guardan en ad_archivo_digital
        if ($request->hasFile('login_illus_left')) {
            $file = $request->file('login_illus_left');
            if ($file && $file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension() ?: '');
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return redirect()->back()->withErrors([
                        'login_illus_left' => 'La ilustración izquierda debe ser una imagen (jpg/png/webp).',
                    ])->withInput();
                }
                $idArchivo = ArchivoDigitalService::store($file, 'Login: Ilustración izquierda');
                if (!$idArchivo) {
                    return redirect()->back()->withErrors([
                        'login_illus_left' => 'No se pudo guardar la ilustración izquierda en ad_archivo_digital.',
                    ])->withInput();
                }
                PgConfiguracion::setValor('LOGIN_ILLUS_LEFT', $idArchivo, 'archivo', 'Login: Ilustración izquierda (ad_archivo_digital)', 'apariencia');
            }
        }

        if ($request->hasFile('login_illus_right')) {
            $file = $request->file('login_illus_right');
            if ($file && $file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension() ?: '');
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return redirect()->back()->withErrors([
                        'login_illus_right' => 'La ilustración derecha debe ser una imagen (jpg/png/webp).',
                    ])->withInput();
                }
                $idArchivo = ArchivoDigitalService::store($file, 'Login: Ilustración derecha');
                if (!$idArchivo) {
                    return redirect()->back()->withErrors([
                        'login_illus_right' => 'No se pudo guardar la ilustración derecha en ad_archivo_digital.',
                    ])->withInput();
                }
                PgConfiguracion::setValor('LOGIN_ILLUS_RIGHT', $idArchivo, 'archivo', 'Login: Ilustración derecha (ad_archivo_digital)', 'apariencia');
            }
        }

        // Logo reportes (solo imagen) -> se guarda en ad_archivo_digital y se referencia por id
        if ($request->hasFile('logo_reportes')) {
            $file = $request->file('logo_reportes');
            if ($file && $file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension() ?: '');
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return redirect()->back()->withErrors([
                        'logo_reportes' => 'El logo de reportes debe ser una imagen (jpg/png/webp).',
                    ])->withInput();
                }
                $idArchivo = ArchivoDigitalService::store($file, 'Logo para reportes');
                if (!$idArchivo) {
                    return redirect()->back()->withErrors([
                        'logo_reportes' => 'No se pudo guardar el logo de reportes en ad_archivo_digital.',
                    ])->withInput();
                }
                // Requisito: pg_configuracion.clave = logo_reportes y tipo = B
                PgConfiguracion::setValor('logo_reportes', $idArchivo, 'B', 'Logo para reportes (ad_archivo_digital)', 'apariencia');
            }
        }

        // Reaplicar runtime (timezone/nombre)
        PgConfiguracion::applyRuntime();

        return redirect()->route('PgConfiguracionesIndex')->with('success', 'Configuraciones guardadas correctamente.');
    }
}
