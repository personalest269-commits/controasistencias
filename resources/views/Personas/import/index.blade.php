@extends("templates.".config("sysconfig.theme").".master")

@section('content')
    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12">
            <h3 style="margin-top:0;">Importación de Personas</h3>
            <p class="text-muted" style="margin-bottom:0;">Carga desde <strong>XLS/XLSX</strong> o desde una <strong>API</strong>. Se guardará primero en una tabla temporal para previsualizar antes de aplicar.</p>
        <div class="alert alert-info" style="margin-top:10px;">
            <i class="fa fa-info-circle"></i>
            ¿No tienes el formato correcto?
            <a href="{{ asset('plantillas/plantilla_importacion_personas.xlsx') }}" download>
                Descarga aquí el archivo de muestra
            </a>.
        </div>

        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin-bottom:0;">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><strong>1) Subir archivo XLS / XLSX</strong></div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('personas.import.xls') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Archivo</label>
                            <input type="file" class="form-control" name="file" accept=".xls,.xlsx" required>
                            <small class="text-muted">Se cargarán únicamente filas con <strong>VIGENTE = 'S'</strong>.</small>
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="fa fa-upload"></i> Cargar y previsualizar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><strong>2) Importar desde API</strong></div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('personas.import.api') }}">
                        @csrf
                        <div class="form-group">
                            <label>URL de la API</label>
                            @php
                                $cfg = isset($apiCfg) ? $apiCfg : null;
                                $qp = ($cfg && is_array($cfg->query_params)) ? $cfg->query_params : [];
                            @endphp
                            {{-- Oculto/Bloqueado: la URL se toma desde Configuración API (Importación Personas) --}}
                            <input type="hidden" name="api_url" value="{{ old('api_url', $cfg->api_url ?? '') }}">
                            <div class="form-control" style="background:#f9f9f9;">
                                <span class="text-muted">(configurada)</span>
                                <span style="margin-left:6px;">
                                    {{ $cfg && !empty($cfg->api_url) ? \Illuminate\Support\Str::limit($cfg->api_url, 70) : '—' }}
                                </span>
                            </div>
                            <small class="text-muted">La API debe devolver un arreglo (o un objeto con <code>data</code>) con los mismos campos del XLS.</small>
                            @if(auth()->user() && auth()->user()->can('api_config_personas_import'))
                                <div style="margin-top:6px;">
                                    <a href="{{ route('api_config.personas_import.edit') }}" class="small"><i class="fa fa-cog"></i> Configurar valores por defecto</a>
                                </div>
                            @endif
                        </div>

                        {{-- Oculto/Bloqueado: la autenticación se toma desde Configuración API (Importación Personas) --}}
                        <input type="hidden" name="auth_type" value="{{ old('auth_type', $cfg->auth_type ?? 'none') }}">
                        <input type="hidden" name="auth_user" value="{{ old('auth_user', $cfg->auth_user ?? '') }}">
                        <input type="hidden" name="auth_pass" value="{{ old('auth_pass', $cfg->auth_pass ?? '') }}">
                        <input type="hidden" name="auth_token" value="{{ old('auth_token', $cfg->auth_token ?? '') }}">

                        <div class="alert alert-info" style="margin:12px 0; padding:10px;">
                            <i class="fa fa-lock"></i>
                            <strong>Autenticación:</strong> configurada por administrador.
                        </div>


                        <div class="row" style="margin-top:8px;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Empresa (solo para guardar en temporal)</label>
                                    <select class="form-control" name="empresa_id">
                                        @foreach(($empresas ?? []) as $e)
                                            <option value="{{ $e['id'] }}" {{ (string)old('empresa_id', $defaultEmpresaId ?? '') === (string)$e['id'] ? 'selected' : '' }}>
                                                {{ $e['nombre'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Este combo <strong>NO</strong> se envía a la API. Solo se guarda como <code>empresa_id</code> en <code>pg_persona_stg</code>.</small>
                                </div>
                            </div>
                        </div>

                        <hr style="margin:12px 0;">

                        <div class="row">
                            <div class="col-md-12">
                                <label style="margin-bottom:6px;">Filtros (Query Params)</label>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="small text-muted">Vigente</label>
                                    <select class="form-control" name="vigente">
                                        <option value="">(Todos)</option>
                                        <option value="S" {{ old('vigente', $qp['vigente'] ?? 'S') === 'S' ? 'selected' : '' }}>S</option>
                                        <option value="N" {{ old('vigente', $qp['vigente'] ?? '') === 'N' ? 'selected' : '' }}>N</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="small text-muted">Size (por página)</label>
                                    <input type="number" class="form-control" name="size" min="1" max="5000" value="{{ old('size', $qp['size'] ?? 700) }}" placeholder="Ej: 700">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="small text-muted">Cod Departamento</label>
                                    <input type="text" class="form-control" name="cod_departamento" value="{{ old('cod_departamento', $qp['cod_departamento'] ?? '') }}" placeholder="Ej: TI">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="small text-muted">Tipo Identificación</label>
                                    <input type="text" class="form-control" name="tipo_identificacion" value="{{ old('tipo_identificacion', $qp['tipo_identificacion'] ?? '') }}" placeholder="C/CED">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="small text-muted">Identificación</label>
                                    <input type="text" class="form-control" name="identificacion" value="{{ old('identificacion', $qp['identificacion'] ?? '') }}" placeholder="010203...">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <small class="text-muted">Nota: el importador pagina con <code>page</code>. Soporta sobres tipo Laravel (<code>current_page/last_page</code>) y el formato de tu API (<code>page</code> + <code>totalPages</code>).</small>
                            </div>
                        </div>

                        <button class="btn btn-info" type="submit"><i class="fa fa-cloud-download"></i> Consultar y previsualizar</button>
                    </form>

                    {{-- auth UI ocultada intencionalmente --}}
                </div>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top:10px;">
        <div class="col-md-12">
            <form method="POST" action="{{ route('personas.import.truncate_stg') }}" style="display:inline;">
                @csrf
                <button class="btn btn-danger" type="submit" onclick="return confirm('⚠️ Esto hará TRUNCATE a pg_persona_stg (se borrará TODO lo temporal). ¿Continuar?')">
                    <i class="fa fa-eraser"></i> Truncar tabla temporal (pg_persona_stg)
                </button>
                <small class="text-muted" style="margin-left:8px;">Útil si quedó basura en temporal y quieres limpiar todo antes de una nueva importación.</small>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning" style="margin-top:10px;">
                <strong>Validaciones:</strong>
                <ul style="margin-bottom:0;">
                    <li>Se actualiza/inserta en <code>pg_persona</code> usando <strong>IDENTIFICACION</strong> como llave de cruce.</li>
                    <li><code>cod_estado_civil</code> se resuelve comparando <code>DESCRIPCION_ESTADO_CIVIL</code> con <code>pg_estado_civil.descripcion</code> (mayúsculas).</li>
                    <li><code>departamento_id</code> se resuelve comparando <code>COD_DEPARTAMENTO</code> con <code>pg_departamento.codigo</code> y guardando <code>pg_departamento.id</code>.</li>
                    <li>En los INSERT <strong>no se envía</strong> <code>pg_persona.id</code> (lo genera el trigger).</li>
                </ul>
            </div>

            <div class="alert alert-info" style="margin-top:10px;">
                <strong>Encabezados esperados del archivo (XLS/XLSX):</strong>
                <div class="table-responsive" style="margin-top:8px;">
                    <table class="table table-condensed table-bordered" style="margin-bottom:0;background:#fff;">
                        <tbody>
                        <tr>
                            <td><code>ID</code></td>
                            <td><code>NOMBRES</code></td>
                            <td><code>APELLIDO1</code></td>
                            <td><code>APELLIDO2</code></td>
                            <td><code>TIPO</code></td>
                            <td><code>DIRECCION</code></td>
                        </tr>
                        <tr>
                            <td><code>VIGENCIA_DESDE</code></td>
                            <td><code>VIGENCIA_HASTA</code></td>
                            <td><code>VIGENTE</code></td>
                            <td><code>COD_DEPARTAMENTO</code></td>
                            <td><code>DEPARTAMENTO</code></td>
                            <td><code>EMAIL</code></td>
                        </tr>
                        <tr>
                            <td><code>EMAIL_LABORAL</code></td>
                            <td><code>IDENTIFICACION</code></td>
                            <td><code>FECHA_NACIMIENTO</code></td>
                            <td><code>TIPO_IDENTIFICACION</code></td>
                            <td><code>DESCRIPCION_IDENTIFICACION</code></td>
                            <td><code>COD_ESTADO_CIVIL</code></td>
                        </tr>
                        <tr>
                            <td><code>DESCRIPCION_ESTADO_CIVIL</code></td>
                            <td><code>FECHA_INGRESO</code></td>
                            <td><code>SEXO</code></td>
                            <td><code>CELULAR</code></td>
                            <td colspan="2" class="text-muted">(se importan solo filas con <code>VIGENTE = 'S'</code>)</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">Nota: también se tolera el encabezado mal escrito <code>NONBRES</code> (se corrige a <code>NOMBRES</code> automáticamente).</small>
            </div>
        </div>
    </div>
@endsection
