@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    @parent
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
    <style>
        .select2-container{ width:100% !important; }
        .select2-container .select2-selection--single{ height:38px; padding:4px 8px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered{ line-height:28px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow{ height:38px; }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Editar departamento</h4>
            <small class="text-muted">{{ $departamento->descripcion }}</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
            <strong>Datos del departamento</strong>
        </div>
        <div class="card-body">
            <form action="{{ route('PgDepartamentosUpdate', $departamento->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ID</label>
                            <input type="text" class="form-control" value="{{ $departamento->id }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Código</label>
                            <input type="text" class="form-control" name="codigo" value="{{ old('codigo', $departamento->codigo) }}" maxlength="10">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Empresa</label>
                            <select class="form-control js-empresa" name="empresa_id">
                                @if(old('empresa_id', $departamento->empresa_id))
                                    <option value="{{ old('empresa_id', $departamento->empresa_id) }}" selected>
                                        {{ $departamento->empresa->nombre ?? old('empresa_id', $departamento->empresa_id) }}
                                    </option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Descripción *</label>
                            <input type="text" class="form-control" name="descripcion" value="{{ old('descripcion', $departamento->descripcion) }}" required maxlength="255">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Código padre</label>
                            <input type="text" class="form-control" name="cod_padre" value="{{ old('cod_padre', $departamento->cod_padre) }}" maxlength="10">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Código programa</label>
                            <input type="text" class="form-control" name="cod_programa" value="{{ old('cod_programa', $departamento->cod_programa) }}" maxlength="10">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Último nivel</label>
                            <select class="form-control" name="ultimo_nivel">
                                <option value="N" {{ old('ultimo_nivel', $departamento->ultimo_nivel) === 'N' ? 'selected' : '' }}>N</option>
                                <option value="S" {{ old('ultimo_nivel', $departamento->ultimo_nivel) === 'S' ? 'selected' : '' }}>S</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Jefe</label>
                            <select class="form-control js-jefe" name="id_jefe">
                                <option value="">-- Seleccione --</option>
                                @foreach($jefes as $p)
                                    <option value="{{ $p->id }}" {{ old('id_jefe', $departamento->id_jefe) === $p->id ? 'selected' : '' }}>
                                        {{ $p->nombre_completo }} ({{ $p->id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Vigencia desde</label>
                            <input type="date" class="form-control" name="vigencia_desde" value="{{ old('vigencia_desde', optional($departamento->vigencia_desde)->format('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Vigencia hasta</label>
                            <input type="date" class="form-control" name="vigencia_hasta" value="{{ old('vigencia_hasta', optional($departamento->vigencia_hasta)->format('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Activo fijo</label>
                            <input type="text" class="form-control" name="identificador_activo_fijo" value="{{ old('identificador_activo_fijo', $departamento->identificador_activo_fijo) }}" maxlength="2">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Extensión</label>
                            <input type="text" class="form-control" name="extension_telefonica" value="{{ old('extension_telefonica', $departamento->extension_telefonica) }}" maxlength="5">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Clasificación</label>
                            <input type="text" class="form-control" name="cod_clasificacion_departamento" value="{{ old('cod_clasificacion_departamento', $departamento->cod_clasificacion_departamento) }}" maxlength="3">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar</button>
                <a class="btn btn-light" href="{{ route('PgDepartamentosIndex') }}">Volver</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer')
    @parent
    <script src="{{ asset('admin_lte/plugins/select2/js/select2.min.js') }}"></script>
    <script>
        (function(){
            $('.js-jefe').select2({ width:'100%' });

            $('.js-empresa').select2({
                width:'100%',
                placeholder: '-- Seleccione --',
                allowClear: true,
                ajax: {
                    url: '{{ route('PgEmpresasSelect2') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term || '' };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                }
            });
        })();
    </script>
@endsection
