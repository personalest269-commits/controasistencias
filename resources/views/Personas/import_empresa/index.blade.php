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
                    <div class="alert alert-warning" style="margin:0;">
                        <i class="fa fa-ban"></i>
                        Este flujo fue deshabilitado para <strong>ImportacionesEmpresa</strong>. Solo se usará importación por archivo XLS/XLSX.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
