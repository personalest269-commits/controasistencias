@extends("templates.".config("sysconfig.theme").".master")

@section('content')
    <div class="row" style="margin-bottom:10px;">
        <div class="col-md-12">
            <h3 style="margin-top:0;">ImportacionesEmpresa</h3>
            <p class="text-muted" style="margin-bottom:0;">Copia del flujo de importación orientado a <strong>empresa/usuario/roles</strong>.</p>
            <div class="alert alert-info" style="margin-top:10px;">
                <i class="fa fa-info-circle"></i>
                <a href="{{ route('importaciones_empresa.formato') }}">Descargar formato de Excel</a>
                <span class="text-muted">(solo con las columnas permitidas para esta carga).</span>
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
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading"><strong>Subir archivo XLS / XLSX</strong></div>
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

                    <hr>
                    <h4 style="margin-top:0;">Formato esperado del Excel</h4>
                    <p class="text-muted">Solo se procesan estas columnas (en este orden):</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed" style="margin-bottom:0;">
                            <thead>
                            <tr>
                                <th>NOMBRES</th>
                                <th>APELLIDO1</th>
                                <th>APELLIDO2</th>
                                <th>DIRECCION</th>
                                <th>VIGENTE</th>
                                <th>COD_DEPARTAMENTO</th>
                                <th>DEPARTAMENTO</th>
                                <th>EMAIL</th>
                                <th>IDENTIFICACION</th>
                                <th>FECHA_NACIMIENTO</th>
                                <th>DESCRIPCION_IDENTIFICACION</th>
                                <th>SEXO</th>
                                <th>CELULAR</th>
                                <th>EMPRESA</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <p class="text-muted" style="margin-top:8px; margin-bottom:0;">
                        Al subir el archivo se mostrará la previsualización del lote antes de aplicar los cambios.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
