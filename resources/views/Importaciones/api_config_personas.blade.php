@extends("templates.".config("sysconfig.theme").".master")

@section('content')
    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12">
            <h3 style="margin-top:0;">Configuración de API (Importación de Personas)</h3>
            <p class="text-muted" style="margin-bottom:0;">Aquí defines valores por defecto para la importación desde API: URL, autenticación y filtros.</p>
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

    @php
        $qp = is_array($cfg->query_params) ? $cfg->query_params : [];
    @endphp

    <div class="panel panel-default">
        <div class="panel-heading"><strong>Parámetros por defecto</strong></div>
        <div class="panel-body">
            <form method="POST" action="{{ route('api_config.personas_import.update') }}">
                @csrf

                <div class="form-group">
                    <label>URL de la API</label>
                    <input type="url" class="form-control" name="api_url" value="{{ old('api_url', $cfg->api_url) }}" placeholder="https://...">
                    <small class="text-muted">Debe devolver un arreglo (o un objeto con <code>data</code>), con los mismos campos del XLS.</small>
                </div>

                <hr style="margin:12px 0;">

                <div class="row">
                    <div class="col-md-12">
                        <label style="margin-bottom:6px;">Autenticación</label>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <select class="form-control" name="auth_type" id="auth_type">
                                <option value="none" {{ old('auth_type', $cfg->auth_type) === 'none' ? 'selected' : '' }}>Ninguno</option>
                                <option value="basic" {{ old('auth_type', $cfg->auth_type) === 'basic' ? 'selected' : '' }}>Basic</option>
                                <option value="bearer" {{ old('auth_type', $cfg->auth_type) === 'bearer' ? 'selected' : '' }}>Bearer</option>
                            </select>
                            <small class="text-muted">Si tu API requiere <code>Authorization: Bearer ...</code>, elige <strong>Bearer</strong>.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" id="auth_basic_wrap" style="display:none;">
                            <div class="row">
                                <div class="col-xs-6">
                                    <input type="text" class="form-control" name="auth_user" value="{{ old('auth_user', $cfg->auth_user) }}" placeholder="Usuario">
                                </div>
                                <div class="col-xs-6">
                                    <input type="password" class="form-control" name="auth_pass" value="{{ old('auth_pass', $cfg->auth_pass) }}" placeholder="Clave">
                                </div>
                            </div>
                        </div>
                        <div class="form-group" id="auth_bearer_wrap" style="display:none;">
                            <input type="text" class="form-control" name="auth_token" value="{{ old('auth_token', $cfg->auth_token) }}" placeholder="Token (sin la palabra Bearer)">
                        </div>
                    </div>
                </div>

                <hr style="margin:12px 0;">

                <div class="row">
                    <div class="col-md-12">
                        <label style="margin-bottom:6px;">Filtros por defecto (Query Params)</label>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small text-muted">Vigente</label>
                            <select class="form-control" name="vigente">
                                <option value="S" {{ old('vigente', $qp['vigente'] ?? 'S') === 'S' ? 'selected' : '' }}>S</option>
                                <option value="N" {{ old('vigente', $qp['vigente'] ?? 'S') === 'N' ? 'selected' : '' }}>N</option>
                            </select>
                            <small class="text-muted">En importación se guardan solo <strong>VIGENTE='S'</strong>.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small text-muted">Size (por página)</label>
                            <input type="number" class="form-control" name="size" min="1" max="5000" value="{{ old('size', $qp['size'] ?? 700) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="small text-muted">Máx. páginas</label>
                            <input type="number" class="form-control" name="max_pages" min="1" max="5000" value="{{ old('max_pages', $qp['max_pages'] ?? 200) }}">
                            <small class="text-muted">Límite de seguridad para evitar demasiadas llamadas (429).</small>
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
                        <small class="text-muted">Nota: el importador paginará con <code>page</code>. Soporta sobres tipo Laravel (<code>current_page/last_page</code>) y también el formato mostrado en tu Postman (<code>page</code> + <code>totalPages</code>).</small>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar</button>
                <a class="btn btn-default" href="{{ route('personas.import.index') }}">Volver a Importación</a>
            </form>
        </div>
    </div>

    <script>
        (function(){
            function toggleAuth(){
                var t = document.getElementById('auth_type').value;
                var b = document.getElementById('auth_basic_wrap');
                var br = document.getElementById('auth_bearer_wrap');
                b.style.display = (t === 'basic') ? 'block' : 'none';
                br.style.display = (t === 'bearer') ? 'block' : 'none';
            }
            var sel = document.getElementById('auth_type');
            sel.addEventListener('change', toggleAuth);
            toggleAuth();
        })();
    </script>
@endsection
