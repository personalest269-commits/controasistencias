@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    @parent
    {{-- Select2 (gratis) para "bambox" buscable --}}
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
    <style>
        .select2-container{ width:100% !important; }
        .select2-container .select2-selection--single{ height:38px; padding:4px 8px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered{ line-height:28px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow{ height:38px; }
        .row-highlight{ background: #d4edda !important; }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Departamentos</h4>
            <small class="text-muted">Gestión de departamentos (incluye jefe, vigencias y datos básicos).</small>
        </div>
    </div>

    @if (session('success'))
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

    <div class="row">
        <div class="col-12">
            <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
                <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
                    <div class="d-flex align-items-center justify-content-between" style="gap:12px; flex-wrap:wrap;">
                        <strong>Listado</strong>
                        <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
                            {{-- Bambox de búsqueda (escriba y filtra la tabla) --}}
                            <input type="text" id="depSearch" class="form-control form-control-sm" style="min-width:260px;" placeholder="Buscar por ID, código, descripción o jefe..." value="{{ $q ?? request('q') }}">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalNuevoDepartamento">
                                Nuevo
                            </button>
                            <a class="btn btn-light btn-sm" href="{{ route('PgDepartamentosEliminados') }}">Ver eliminados</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empresa</th>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th>Jefe</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departamentos as $d)
                                    <tr id="dep-row-{{ $d->id }}">
                                        <td>{{ $d->id }}</td>
                                        <td>{{ $d->empresa->nombre ?? '' }}</td>
                                        <td>{{ $d->codigo }}</td>
                                        <td>{{ $d->descripcion }}</td>
                                        <td>
                                            @php $j = $jefes->firstWhere('id', $d->id_jefe); @endphp
                                            {{ $j->nombre_completo ?? '' }}
                                        </td>
                                        <td class="text-right">
                                            <a class="btn btn-info btn-sm" href="{{ route('PgDepartamentosEdit', $d->id) }}">Editar</a>
                                            <form action="{{ route('PgDepartamentosDelete', $d->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este departamento?');">
                                                @csrf
                                                <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">Sin registros.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer" style="background:#fff; border-top:1px solid #e9ecef;">
                    {{ $departamentos->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Nuevo departamento --}}
<div class="modal fade" id="modalNuevoDepartamento" tabindex="-1" role="dialog" aria-labelledby="modalNuevoDepartamentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoDepartamentoLabel">Nuevo departamento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('PgDepartamentosStore') }}" method="POST" id="formNuevoDepartamento">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Empresa</label>
                                {{-- Bambox: Select2 permite escribir y buscar empresas --}}
                                <select class="form-control js-empresa" name="empresa_id">
                                    @if(old('empresa_id'))
                                        <option value="{{ old('empresa_id') }}" selected>{{ old('empresa_id') }}</option>
                                    @endif
                                </select>
                                <small class="text-muted">Si aún no asignas empresa, puedes dejarlo vacío (por ahora).</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Código</label>
                                <input type="text" class="form-control" name="codigo" value="{{ old('codigo') }}" maxlength="10">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Descripción *</label>
                                <input type="text" class="form-control" name="descripcion" value="{{ old('descripcion') }}" required maxlength="255">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Código padre</label>
                                <input type="text" class="form-control" name="cod_padre" value="{{ old('cod_padre') }}" maxlength="10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Código programa</label>
                                <input type="text" class="form-control" name="cod_programa" value="{{ old('cod_programa') }}" maxlength="10">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Último nivel</label>
                                <select class="form-control" name="ultimo_nivel">
                                    <option value="N" {{ old('ultimo_nivel','N') === 'N' ? 'selected' : '' }}>N</option>
                                    <option value="S" {{ old('ultimo_nivel') === 'S' ? 'selected' : '' }}>S</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Jefe</label>
                                {{-- Bambox: Select2 permite escribir y buscar en la lista --}}
                                <select class="form-control js-jefe" name="id_jefe">
                                    <option value="">-- Seleccione --</option>
                                    @foreach($jefes as $p)
                                        <option value="{{ $p->id }}" {{ old('id_jefe') === $p->id ? 'selected' : '' }}>
                                            {{ $p->nombre_completo }} ({{ $p->id }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vigencia desde</label>
                                <input type="date" class="form-control" name="vigencia_desde" value="{{ old('vigencia_desde') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vigencia hasta</label>
                                <input type="date" class="form-control" name="vigencia_hasta" value="{{ old('vigencia_hasta') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Activo fijo</label>
                                <input type="text" class="form-control" name="identificador_activo_fijo" value="{{ old('identificador_activo_fijo') }}" maxlength="2">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Extensión</label>
                                <input type="text" class="form-control" name="extension_telefonica" value="{{ old('extension_telefonica') }}" maxlength="5">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Clasificación</label>
                        <input type="text" class="form-control" name="cod_clasificacion_departamento" value="{{ old('cod_clasificacion_departamento') }}" maxlength="3">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
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
            // Select2 (bambox escribible) para jefe
            $('.js-jefe').select2({
                width: '100%',
                dropdownParent: $('#modalNuevoDepartamento')
            });

            $('.js-empresa').select2({
                width: '100%',
                dropdownParent: $('#modalNuevoDepartamento'),
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

            // Filtro rápido de tabla
            const $search = $('#depSearch');
            const $rows = $('table tbody tr');
            function applyFilter(){
                const q = ($search.val() || '').toLowerCase().trim();
                if(!q){
                    $rows.show();
                    return;
                }
                $rows.each(function(){
                    const t = $(this).text().toLowerCase();
                    $(this).toggle(t.indexOf(q) !== -1);
                });
            }
            $search.on('keyup', applyFilter);

            // Enter => buscar en servidor (útil si hay muchas páginas)
            $search.on('keydown', function(e){
                if(e.key === 'Enter'){
                    e.preventDefault();
                    const v = ($search.val() || '').trim();
                    window.location.href = @json(route('PgDepartamentosIndex')) + (v ? ('?q=' + encodeURIComponent(v)) : '');
                }
            });

            // Aplicar filtro inicial si ya hay valor
            if(($search.val() || '').trim() !== ''){
                applyFilter();
            }

            // Si hubo errores, reabrir el modal para que el usuario corrija
            @if($errors->any())
                $('#modalNuevoDepartamento').modal('show');
            @endif

            // Si se creó un departamento, dejarlo "seleccionado":
            // 1) filtrar por ID para que se vea
            // 2) resaltar la fila
            @if(session('created_id'))
                (function(){
                    const createdId = @json(session('created_id'));
                    if(createdId){
                        $search.val(createdId);
                        applyFilter();
                        const $row = $('#dep-row-' + createdId);
                        if($row.length){
                            $row.addClass('row-highlight');
                            $row[0].scrollIntoView({behavior:'smooth', block:'center'});
                        }
                    }
                })();
            @endif
        })();
    </script>
@endsection
