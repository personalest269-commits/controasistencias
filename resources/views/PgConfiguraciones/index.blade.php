@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Configuraciones del sistema</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('PgConfiguracionesUpdate') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">General</h5>

                                <div class="form-group">
                                    <label>Nombre del sistema</label>
                                    <input type="text" class="form-control" name="configs[NOMBRE_SISTEMA]" value="{{ old('configs.NOMBRE_SISTEMA', optional($configs->where('clave','NOMBRE_SISTEMA')->first())->valor) }}">
                                    <small class="text-muted">Se usa como título y textos en login.</small>
                                </div>

                                <div class="form-group">
                                    <label>Zona horaria (APP_TIMEZONE)</label>
                                    <input type="text" class="form-control" name="configs[APP_TIMEZONE]" value="{{ old('configs.APP_TIMEZONE', optional($configs->where('clave','APP_TIMEZONE')->first())->valor) }}" placeholder="America/Guayaquil">
                                    <small class="text-muted">Ej: America/Guayaquil, America/Lima, UTC.</small>
                                </div>

                                <div class="form-group">
                                    <label>Formato de fecha</label>
                                    <input type="text" class="form-control" name="configs[FORMATO_FECHA]" value="{{ old('configs.FORMATO_FECHA', optional($configs->where('clave','FORMATO_FECHA')->first())->valor) }}" placeholder="Y-m-d H:i:s">
                                    <small class="text-muted">Ej: d/m/Y H:i, Y-m-d H:i:s.</small>
                                </div>

                                <div class="form-group">
                                    <label>Items por página</label>
                                    <input type="number" class="form-control" name="configs[ITEMS_POR_PAGINA]" value="{{ old('configs.ITEMS_POR_PAGINA', optional($configs->where('clave','ITEMS_POR_PAGINA')->first())->valor ?? 30) }}" min="1" max="500">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Seguridad y módulos</h5>

                                @php
                                    $correo = (optional($configs->where('clave','CORREO_ACTIVO')->first())->valor ?? 'S') === 'S';
                                    $registro = (optional($configs->where('clave','REGISTRO_USUARIO_ACTIVO')->first())->valor ?? 'S') === 'S';
                                    $frontend = (optional($configs->where('clave','FRONTEND_ACTIVO')->first())->valor ?? 'S') === 'S';
                                    $loginTpl = optional($configs->where('clave','LOGIN_TEMPLATE')->first())->valor ?? 'DEFAULT';

                                    $recSite = trim((string) (optional($configs->where('clave','RECAPTCHA_SITE_KEY')->first())->valor ?? ''));
                                    $recSecret = trim((string) (optional($configs->where('clave','RECAPTCHA_SECRET_KEY')->first())->valor ?? ''));
                                    $recEnabled = ($recSite !== '' && $recSecret !== '');
                                

                                    $uiFrontendLink = (optional($configs->where('clave','UI_LINK_FRONTEND_ACTIVO')->first())->valor ?? 'S') === 'S';
                                    $uiTemplateSwitch = (optional($configs->where('clave','UI_SWITCH_TEMPLATE_ACTIVO')->first())->valor ?? 'S') === 'S';
@endphp

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="CORREO_ACTIVO" name="configs[CORREO_ACTIVO]" @if(old('configs.CORREO_ACTIVO', $correo) ) checked @endif>
                                        <label class="custom-control-label" for="CORREO_ACTIVO">Activar envío de correos</label>
                                    </div>
                                    <small class="text-muted">Si está desactivado, el sistema NO enviará correos (reset, notificaciones, etc.).</small>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="REGISTRO_USUARIO_ACTIVO" name="configs[REGISTRO_USUARIO_ACTIVO]" @if(old('configs.REGISTRO_USUARIO_ACTIVO', $registro) ) checked @endif>
                                        <label class="custom-control-label" for="REGISTRO_USUARIO_ACTIVO">Permitir registro de usuarios</label>
                                    </div>
                                    <small class="text-muted">Oculta la pantalla de registro y bloquea POST /admin/register.</small>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="FRONTEND_ACTIVO" name="configs[FRONTEND_ACTIVO]" @if(old('configs.FRONTEND_ACTIVO', $frontend) ) checked @endif>
                                        <label class="custom-control-label" for="FRONTEND_ACTIVO">Activar frontend (sitio)</label>
                                    </div>
                                    <small class="text-muted">Si está en OFF, cualquier ruta del frontend redirige al login (/admin/login).</small>
                                </div>

                                <h5 class="mt-4 mb-3">Captcha (login)</h5>

                                <div class="alert alert-{{ $recEnabled ? 'success' : 'secondary' }} py-2" role="alert" style="font-size: 14px;">
                                    <strong>Estado:</strong>
                                    @if($recEnabled)
                                        <span>Habilitado</span>
                                    @else
                                        <span>Deshabilitado</span>
                                    @endif
                                    <div class="mt-1 text-muted" style="font-size: 12px;">
                                        El captcha se habilita automáticamente <strong>solo</strong> si ingresas <em>Site Key</em> y <em>Secret Key</em>.
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>reCAPTCHA Site Key</label>
                                    <input type="text" class="form-control" name="configs[RECAPTCHA_SITE_KEY]" value="{{ old('configs.RECAPTCHA_SITE_KEY', $recSite) }}" placeholder="Ej: 6Lc...">
                                    <small class="text-muted">Se usa para mostrar el widget en el login.</small>
                                </div>

                                <div class="form-group">
                                    <label>reCAPTCHA Secret Key</label>
                                    <input type="text" class="form-control" name="configs[RECAPTCHA_SECRET_KEY]" value="{{ old('configs.RECAPTCHA_SECRET_KEY', $recSecret) }}" placeholder="Ej: 6Lc...">
                                    <small class="text-muted">Se usa en backend para validar el token contra Google.</small>
                                </div>

                                <h5 class="mt-4 mb-3">Apariencia</h5>

                                <div class="form-group">
                                    <label>Plantilla de login</label>
                                    <select class="form-control" name="configs[LOGIN_TEMPLATE]">
                                        @php($curTpl = strtoupper(old('configs.LOGIN_TEMPLATE', $loginTpl)))
                                        <option value="DEFAULT" @if($curTpl==='DEFAULT') selected @endif>Default (según theme)</option>
                                        <option value="CONTROL" @if($curTpl==='CONTROL') selected @endif>CONTROL (diseño tipo Control)</option>
                                    </select>
                                    <small class="text-muted">Define qué pantalla se muestra en <code>/admin/login</code>.</small>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="UI_LINK_FRONTEND_ACTIVO" name="configs[UI_LINK_FRONTEND_ACTIVO]" @if(old('configs.UI_LINK_FRONTEND_ACTIVO', $uiFrontendLink) ) checked @endif>
                                        <label class="custom-control-label" for="UI_LINK_FRONTEND_ACTIVO">Mostrar link Frontend (barra superior)</label>
                                    </div>
                                    <small class="text-muted">Si está en OFF, no aparecerá el enlace <strong>Frontend</strong> en el header.</small>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="UI_SWITCH_TEMPLATE_ACTIVO" name="configs[UI_SWITCH_TEMPLATE_ACTIVO]" @if(old('configs.UI_SWITCH_TEMPLATE_ACTIVO', $uiTemplateSwitch) ) checked @endif>
                                        <label class="custom-control-label" for="UI_SWITCH_TEMPLATE_ACTIVO">Mostrar selector AdminLTE (plantilla)</label>
                                    </div>
                                    <small class="text-muted">Si está en OFF, no aparecerá el selector de plantilla (AdminLTE/Gentelella) en el header.</small>
                                </div>

                                <div class="form-group">
                                    <label>Logo del sistema</label>
                                    <input type="file" class="form-control" name="logo_sistema" accept="image/*">
                                    @if(!empty($logoUrl))
                                        <div class="mt-2">
                                            <div class="text-muted" style="font-size:12px;">Vista previa:</div>
                                            <img src="{{ $logoUrl }}" alt="logo" style="max-height:60px; max-width:220px; background:#fff; padding:6px; border-radius:6px; border:1px solid #ddd;">
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>Logo para reportes (PDF/Excel)</label>
                                    <input type="file" class="form-control" name="logo_reportes" accept="image/jpeg,image/png,image/webp">
                                    <small class="text-muted">Se guarda en <code>ad_archivo_digital</code> y se usa como prioridad en reportes. Si no existe, se usa <code>LOGO_SISTEMA</code>.</small>
                                    @if(!empty($logoReportesUrl))
                                        <div class="mt-2">
                                            <div class="text-muted" style="font-size:12px;">Vista previa:</div>
                                            <img src="{{ $logoReportesUrl }}" alt="logo_reportes" style="max-height:60px; max-width:220px; background:#fff; padding:6px; border-radius:6px; border:1px solid #ddd;">
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>Ilustración login izquierda</label>
                                    <input type="file" class="form-control" name="login_illus_left" accept="image/jpeg,image/png,image/webp">
                                    <small class="text-muted">Se guarda en <code>ad_archivo_digital</code> y se muestra en la pantalla de login CONTROL.</small>
                                    @if(!empty($loginLeftUrl))
                                        <div class="mt-2">
                                            <div class="text-muted" style="font-size:12px;">Vista previa:</div>
                                            <img src="{{ $loginLeftUrl }}" alt="login_left" style="max-height:120px; max-width:260px; background:#fff; padding:6px; border-radius:6px; border:1px solid #ddd;">
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>Ilustración login derecha</label>
                                    <input type="file" class="form-control" name="login_illus_right" accept="image/jpeg,image/png,image/webp">
                                    <small class="text-muted">Se guarda en <code>ad_archivo_digital</code> y se muestra en la pantalla de login CONTROL.</small>
                                    @if(!empty($loginRightUrl))
                                        <div class="mt-2">
                                            <div class="text-muted" style="font-size:12px;">Vista previa:</div>
                                            <img src="{{ $loginRightUrl }}" alt="login_right" style="max-height:120px; max-width:260px; background:#fff; padding:6px; border-radius:6px; border:1px solid #ddd;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <a href="{{ route('dashboardIndex') }}" class="btn btn-light">Volver</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
