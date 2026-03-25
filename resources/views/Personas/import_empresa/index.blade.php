@extends("templates.".config("sysconfig.theme").".master")

@section('content')
    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12">
            <h3 style="margin-top:0;">ImportacionesEmpresa</h3>
            <p class="text-muted" style="margin-bottom:0;">Copia del flujo de importación orientado a <strong>empresa/usuario/roles</strong>.</p>
            <div class="alert alert-info" style="margin-top:10px;">
                <i class="fa fa-info-circle"></i>
                <a href="{{ route('importaciones_empresa.formato') }}">Descargar formato automático por empresa</a>
                <span class="text-muted">(incluye los nuevos campos de <code>pg_persona_stg</code> y completa EMPRESA por defecto).</span>
            </div>
            @if(!$isAdminImport)
                <div class="alert alert-warning" style="margin-top:10px;">
                    Usuario restringido a empresa: <strong>{{ $empresaNombre ?: 'No definida' }}</strong>.
                    Solo se importarán filas de esa empresa.
                </div>
            @endif
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
                    <form method="POST" action="{{ route('importaciones_empresa.xls') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Archivo</label>
                            <input type="file" class="form-control" name="file" accept=".xls,.xlsx" required>
                            <small class="text-muted">Se cargan filas con <strong>VIGENTE = 'S'</strong> y empresa válida en <code>pg_empresa</code>.</small>
                        </div>

                        <div class="form-group">
                            <label>Empresa</label>
                            <select class="form-control" name="empresa_id" {{ !$isAdminImport ? 'disabled' : '' }}>
                                @foreach(($empresas ?? []) as $e)
                                    <option value="{{ $e['id'] }}" {{ (string)($defaultEmpresaId ?? '') === (string)$e['id'] ? 'selected' : '' }}>{{ $e['nombre'] }}</option>
                                @endforeach
                            </select>
                            @if(!$isAdminImport)
                                <input type="hidden" name="empresa_id" value="{{ $defaultEmpresaId }}">
                            @endif
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
                    <form method="POST" action="{{ route('importaciones_empresa.api') }}">
                        @csrf
                        @php
                            $cfg = isset($apiCfg) ? $apiCfg : null;
                            $qp = ($cfg && is_array($cfg->query_params)) ? $cfg->query_params : [];
                        @endphp

                        <div class="alert alert-info" style="margin:0 0 12px 0; padding:10px;">
                            <i class="fa fa-lock"></i>
                            API y autenticación se toman desde configuración global.
                        </div>

                        <input type="hidden" name="api_url" value="{{ old('api_url', $cfg->api_url ?? '') }}">
                        <input type="hidden" name="auth_type" value="{{ old('auth_type', $cfg->auth_type ?? 'none') }}">
                        <input type="hidden" name="auth_user" value="{{ old('auth_user', $cfg->auth_user ?? '') }}">
                        <input type="hidden" name="auth_pass" value="{{ old('auth_pass', $cfg->auth_pass ?? '') }}">
                        <input type="hidden" name="auth_token" value="{{ old('auth_token', $cfg->auth_token ?? '') }}">
                        <input type="hidden" name="empresa_id" value="{{ $defaultEmpresaId }}">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="small text-muted">Vigente</label>
                                    <select class="form-control" name="vigente">
                                        <option value="S" {{ old('vigente', $qp['vigente'] ?? 'S') === 'S' ? 'selected' : '' }}>S</option>
                                        <option value="N" {{ old('vigente', $qp['vigente'] ?? '') === 'N' ? 'selected' : '' }}>N</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="small text-muted">Size</label>
                                    <input type="number" class="form-control" name="size" min="1" max="5000" value="{{ old('size', $qp['size'] ?? 700) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="small text-muted">Cod Depto</label>
                                    <input type="text" class="form-control" name="cod_departamento" value="{{ old('cod_departamento', $qp['cod_departamento'] ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-info" type="submit"><i class="fa fa-cloud-download"></i> Consultar y previsualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
