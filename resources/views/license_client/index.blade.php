@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<style>
    .license-page .page-title {
        margin-top: 0;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .license-page .box {
        margin-bottom: 20px;
    }

    .license-page .box-header h3 {
        font-size: 18px;
        font-weight: 600;
    }

    .license-page .form-group label {
        font-weight: 600;
        margin-bottom: 6px;
    }

    .license-page .form-control,
    .license-page select,
    .license-page textarea {
        border-radius: 4px;
    }

    .license-page textarea {
        resize: vertical;
    }

    .license-page .status-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .license-page .status-list li {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .license-page .status-list li:last-child {
        border-bottom: none;
    }

    .license-page .status-label {
        display: block;
        font-weight: 700;
        color: #444;
        margin-bottom: 2px;
    }

    .license-page .status-value {
        display: block;
        color: #666;
        word-break: break-word;
    }

    .license-page .status-value small {
        word-break: break-all;
    }

    .license-page .response-box {
        background: #f7f7f7;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
        padding: 12px;
        max-height: 320px;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
        margin: 0;
    }

    .license-page .action-form {
        margin-bottom: 10px;
    }

    .license-page .action-form:last-child {
        margin-bottom: 0;
    }

    .license-page .btn-block {
        font-weight: 600;
    }

    .license-page .section-note {
        color: #888;
        font-size: 12px;
        margin-top: -5px;
        margin-bottom: 15px;
    }

    /* Evita cajas “aplastadas” por estilos heredados del tema */
    .license-page .box,
    .license-page .box-body,
    .license-page .box-header {
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
    }

    @media (max-width: 991px) {
        .license-page .page-title {
            margin-bottom: 15px;
        }
    }
</style>

<div class="license-page">
    <div class="row">
        <div class="col-md-12">
            <h3 class="page-title">Licencia del sistema</h3>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        {{-- Columna principal --}}
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Configuración del cliente</h3>
                </div>

                <div class="box-body">
                    <p class="section-note">Configura la conexión con el servidor de licencias y las políticas de validación.</p>

                    <form method="POST" action="{{ route('license-client.save') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>Servidor de licencias</label>
                                <input
                                    type="text"
                                    name="LICENCIA_SERVIDOR_URL"
                                    class="form-control"
                                    value="{{ old('LICENCIA_SERVIDOR_URL', $configs['LICENCIA_SERVIDOR_URL']) }}"
                                >
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Clave de licencia</label>
                                <input
                                    type="text"
                                    name="LICENCIA_CLAVE"
                                    class="form-control"
                                    value="{{ old('LICENCIA_CLAVE', $configs['LICENCIA_CLAVE']) }}"
                                >
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Código de producto</label>
                                <input
                                    type="text"
                                    name="LICENCIA_PRODUCTO_CODIGO"
                                    class="form-control"
                                    value="{{ old('LICENCIA_PRODUCTO_CODIGO', $configs['LICENCIA_PRODUCTO_CODIGO']) }}"
                                >
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Dominio reportado</label>
                                <input
                                    type="text"
                                    name="LICENCIA_DOMINIO"
                                    class="form-control"
                                    value="{{ old('LICENCIA_DOMINIO', $configs['LICENCIA_DOMINIO']) }}"
                                >
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Instalación ID</label>
                                <input
                                    type="text"
                                    name="LICENCIA_INSTALACION_ID"
                                    class="form-control"
                                    value="{{ old('LICENCIA_INSTALACION_ID', $configs['LICENCIA_INSTALACION_ID']) }}"
                                >
                            </div>

                            <div class="col-md-3 form-group">
                                <label>Validar cada minutos</label>
                                <input
                                    type="number"
                                    min="1"
                                    name="LICENCIA_VALIDAR_CADA_MINUTOS"
                                    class="form-control"
                                    value="{{ old('LICENCIA_VALIDAR_CADA_MINUTOS', $configs['LICENCIA_VALIDAR_CADA_MINUTOS']) }}"
                                >
                            </div>

                            <div class="col-md-3 form-group">
                                <label>Gracia horas</label>
                                <input
                                    type="number"
                                    min="0"
                                    name="LICENCIA_GRACIA_HORAS"
                                    class="form-control"
                                    value="{{ old('LICENCIA_GRACIA_HORAS', $configs['LICENCIA_GRACIA_HORAS']) }}"
                                >
                            </div>

                            <div class="col-md-3 form-group">
                                <label>Timeout segundos</label>
                                <input
                                    type="number"
                                    min="3"
                                    name="LICENCIA_TIMEOUT_SEGUNDOS"
                                    class="form-control"
                                    value="{{ old('LICENCIA_TIMEOUT_SEGUNDOS', $configs['LICENCIA_TIMEOUT_SEGUNDOS']) }}"
                                >
                            </div>

                            <div class="col-md-3 form-group">
                                <label>Bloquear si falta configuración</label>
                                <select name="LICENCIA_BLOQUEAR_SIN_CONFIG" class="form-control">
                                    <option value="N" {{ old('LICENCIA_BLOQUEAR_SIN_CONFIG', $configs['LICENCIA_BLOQUEAR_SIN_CONFIG']) === 'N' ? 'selected' : '' }}>No</option>
                                    <option value="S" {{ old('LICENCIA_BLOQUEAR_SIN_CONFIG', $configs['LICENCIA_BLOQUEAR_SIN_CONFIG']) === 'S' ? 'selected' : '' }}>Sí</option>
                                </select>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Activación automática</label>
                                <select name="LICENCIA_AUTO_ACTIVAR" class="form-control">
                                    <option value="S" {{ old('LICENCIA_AUTO_ACTIVAR', $configs['LICENCIA_AUTO_ACTIVAR']) === 'S' ? 'selected' : '' }}>Sí</option>
                                    <option value="N" {{ old('LICENCIA_AUTO_ACTIVAR', $configs['LICENCIA_AUTO_ACTIVAR']) === 'N' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Bloqueo por hardware</label>
                                <select name="LICENCIA_BLOQUEO_HARDWARE" class="form-control">
                                    <option value="S" {{ old('LICENCIA_BLOQUEO_HARDWARE', $configs['LICENCIA_BLOQUEO_HARDWARE']) === 'S' ? 'selected' : '' }}>Sí</option>
                                    <option value="N" {{ old('LICENCIA_BLOQUEO_HARDWARE', $configs['LICENCIA_BLOQUEO_HARDWARE']) === 'N' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Protección contra clonación</label>
                                <select name="LICENCIA_PROTEGER_CLONACION" class="form-control">
                                    <option value="S" {{ old('LICENCIA_PROTEGER_CLONACION', $configs['LICENCIA_PROTEGER_CLONACION']) === 'S' ? 'selected' : '' }}>Sí</option>
                                    <option value="N" {{ old('LICENCIA_PROTEGER_CLONACION', $configs['LICENCIA_PROTEGER_CLONACION']) === 'N' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Versión actual</label>
                                <input
                                    type="text"
                                    name="LICENCIA_VERSION_ACTUAL"
                                    class="form-control"
                                    value="{{ old('LICENCIA_VERSION_ACTUAL', $configs['LICENCIA_VERSION_ACTUAL']) }}"
                                >
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Actualizaciones automáticas</label>
                                <select name="LICENCIA_UPDATE_AUTO_ACTIVO" class="form-control">
                                    <option value="N" {{ old('LICENCIA_UPDATE_AUTO_ACTIVO', $configs['LICENCIA_UPDATE_AUTO_ACTIVO']) === 'N' ? 'selected' : '' }}>No</option>
                                    <option value="S" {{ old('LICENCIA_UPDATE_AUTO_ACTIVO', $configs['LICENCIA_UPDATE_AUTO_ACTIVO']) === 'S' ? 'selected' : '' }}>Sí</option>
                                </select>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Revisar updates cada minutos</label>
                                <input
                                    type="number"
                                    min="5"
                                    name="LICENCIA_UPDATE_CHECK_MINUTOS"
                                    class="form-control"
                                    value="{{ old('LICENCIA_UPDATE_CHECK_MINUTOS', $configs['LICENCIA_UPDATE_CHECK_MINUTOS']) }}"
                                >
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Endpoint de updates</label>
                                <input
                                    type="text"
                                    name="LICENCIA_UPDATE_ENDPOINT"
                                    class="form-control"
                                    value="{{ old('LICENCIA_UPDATE_ENDPOINT', $configs['LICENCIA_UPDATE_ENDPOINT']) }}"
                                >
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Aplicar update automáticamente</label>
                                <select name="LICENCIA_UPDATE_AUTO_APLICAR" class="form-control">
                                    <option value="N" {{ old('LICENCIA_UPDATE_AUTO_APLICAR', $configs['LICENCIA_UPDATE_AUTO_APLICAR']) === 'N' ? 'selected' : '' }}>No</option>
                                    <option value="S" {{ old('LICENCIA_UPDATE_AUTO_APLICAR', $configs['LICENCIA_UPDATE_AUTO_APLICAR']) === 'S' ? 'selected' : '' }}>Sí</option>
                                </select>
                            </div>

                            <div class="col-md-12 form-group">
                                <label>Llave pública RSA (PEM)</label>
                                <textarea
                                    name="LICENCIA_RSA_PUBLIC_KEY"
                                    rows="6"
                                    class="form-control"
                                >{{ old('LICENCIA_RSA_PUBLIC_KEY', $configs['LICENCIA_RSA_PUBLIC_KEY']) }}</textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Guardar configuración
                        </button>
                    </form>
                </div>
            </div>

            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Última respuesta del servidor</h3>
                </div>
                <div class="box-body">
                    <pre class="response-box">{{ $rawResponse }}</pre>
                </div>
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Estado actual</h3>
                </div>

                <div class="box-body">
                    <ul class="status-list">
                        <li>
                            <span class="status-label">Configurado</span>
                            <span class="status-value">{{ $summary['configured'] ? 'Sí' : 'No' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Producto</span>
                            <span class="status-value">{{ $summary['product_code'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Dominio</span>
                            <span class="status-value">{{ $summary['domain'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Instalación</span>
                            <span class="status-value">{{ $summary['installation_id'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Último estado</span>
                            <span class="status-value">{{ $summary['status'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Último mensaje</span>
                            <span class="status-value">{{ $summary['message'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Última validación</span>
                            <span class="status-value">{{ $summary['last_validation_at'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Última validación OK</span>
                            <span class="status-value">{{ $summary['last_ok_at'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">HTTP</span>
                            <span class="status-value">{{ $summary['http_code'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Huella hardware</span>
                            <span class="status-value"><small>{{ $summary['hardware_fingerprint'] ?: '-' }}</small></span>
                        </li>
                        <li>
                            <span class="status-label">Huella clonación</span>
                            <span class="status-value"><small>{{ $summary['clone_fingerprint'] ?: '-' }}</small></span>
                        </li>
                        <li>
                            <span class="status-label">Updates</span>
                            <span class="status-value">{{ $summary['update_state'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Mensaje update</span>
                            <span class="status-value">{{ $summary['update_message'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Versión remota</span>
                            <span class="status-value">{{ $summary['update_version'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Paquete descargado</span>
                            <span class="status-value">{{ $summary['update_package_name'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Listo para aplicar</span>
                            <span class="status-value">{{ $summary['update_ready'] ? 'Sí' : 'No' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Última aplicación</span>
                            <span class="status-value">{{ $summary['update_last_apply_at'] ?: '-' }}</span>
                        </li>
                        <li>
                            <span class="status-label">Estado aplicación</span>
                            <span class="status-value">{{ $summary['update_last_apply_state'] ?: '-' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Acciones</h3>
                </div>

                <div class="box-body">
                    <form method="POST" action="{{ route('license-client.validate') }}" class="action-form">
                        @csrf
                        <button class="btn btn-success btn-block" type="submit">Validar ahora</button>
                    </form>

                    <form method="POST" action="{{ route('license-client.activate') }}" class="action-form">
                        @csrf
                        <button class="btn btn-primary btn-block" type="submit">Activar instalación</button>
                    </form>

                    <form method="POST" action="{{ route('license-client.status') }}" class="action-form">
                        @csrf
                        <button class="btn btn-info btn-block" type="submit">Consultar estado</button>
                    </form>

                    <form method="POST" action="{{ route('license-client.check-updates') }}" class="action-form">
                        @csrf
                        <button class="btn btn-warning btn-block" type="submit">Buscar actualizaciones</button>
                    </form>

                    <form method="POST" action="{{ route('license-client.download-update') }}" class="action-form">
                        @csrf
                        <button class="btn btn-default btn-block" type="submit">Descargar update</button>
                    </form>

                    <form method="POST" action="{{ route('license-client.apply-update') }}" class="action-form" onsubmit="return confirm('Esto reemplazará archivos del proyecto. Se conservará .env y storage. ¿Deseas continuar?');">
                        @csrf
                        <button class="btn btn-warning btn-block" type="submit">Aplicar update descargado</button>
                    </form>

                    <form method="POST" action="{{ route('license-client.deactivate') }}" class="action-form">
                        @csrf
                        <button class="btn btn-danger btn-block" type="submit">Desactivar instalación</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection