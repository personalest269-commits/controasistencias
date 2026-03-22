@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <style>
        .pg-card{
            border:0;
            border-radius:12px;
            box-shadow: 0 10px 25px rgba(0,0,0,.06);
        }
        .pg-card .card-header{
            background:#fff;
            border-bottom:1px solid #e9ecef;
            border-top-left-radius:12px;
            border-top-right-radius:12px;
        }
        .pg-filter{
            background:#fff;
            border:1px solid #e9ecef;
            border-radius:12px;
            padding:16px;
        }
        .select2-container .select2-selection--multiple{
            min-height:38px;
            border-radius:10px;
            border:1px solid #ced4da;
        }
        .badge-status{
            font-size:11px;
            padding:4px 8px;
            border-radius:999px;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Administrar asistencia masiva por empresa</h4>
            <small class="text-muted">Marca asistencia por evento. Si seleccionas una empresa, se pre-seleccionan todos los eventos aplicables por persona.</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('PgAsistenciasEmpresaIndex') }}">
        <div class="pg-filter mb-3">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="mb-1">Fecha</label>
                    <input type="text" id="fecha" name="fecha" value="{{ $fecha }}" class="form-control" autocomplete="off" />
                </div>
                <div class="col-md-4">
                    <label class="mb-1">Empresa</label>
                    <select id="empresa_id" name="empresa_id" class="form-control">
                        <option value="">-- General (todas) --</option>
                        @foreach($empresas as $e)
                            <option value="{{ $e->id }}" {{ ($empresaId==$e->id)?'selected':'' }}>{{ $e->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="mb-1">Persona</label>
                    <select id="persona_id" name="persona_id" class="form-control" style="width:100%">
                        @if(!empty($personaId) && $personas->count()===1)
                            <option value="{{ $personas->first()->id }}" selected>
                                {{ $personas->first()->identificacion ? ($personas->first()->identificacion.' — ') : '' }}{{ $personas->first()->nombre_completo }}
                            </option>
                        @endif
                    </select>
                 
                </div>
                <div class="col-md-2 text-right">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>

            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    En esta pantalla se puede cargar 1 foto por persona (evidencia general).
                </small>
				  
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('PgAsistenciasEmpresaActualizar') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="fecha" value="{{ $fecha }}" />
        <input type="hidden" name="empresa_id" value="{{ $empresaId }}" />

        <div class="card pg-card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <strong>Listado de empleados</strong>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        <div class="custom-control custom-checkbox mr-2">
                            <input type="checkbox" class="custom-control-input" id="chkGeneral">
                            <label class="custom-control-label" for="chkGeneral">Marcar general</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-2">
                            <input type="checkbox" class="custom-control-input" id="chkAutoSave">
                            <label class="custom-control-label" for="chkAutoSave">Auto-actualizar</label>
                        </div>
                        <input type="hidden" name="auto_close" id="auto_close" value="0" />
                        <a href="{{ route('PgAsistenciasEmpresaReportes') }}" class="btn btn-warning btn-sm" id="btnReportes">Reportes</a>
                        <button class="btn btn-primary btn-sm" type="submit" id="btnCerrarDia"
                                name="cerrar_dia" value="1"
                                formaction="{{ route('PgAsistenciasEmpresaCerrarDia') }}">
                            Cerrar asistencia del día
                        </button>
                        <button class="btn btn-success btn-sm" type="submit" id="btnActualizar">Actualizar</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(($events ?? collect())->count() === 0)
                    <div class="alert alert-warning mb-3">
                        No existen eventos creados para la fecha seleccionada. Debe crear eventos para poder <strong>Cerrar asistencia del día</strong> y/o <strong>Actualizar</strong>.
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:170px;">IDENTIFICACIÓN</th>
                                <th>EMPLEADO</th>
                                <th>DEPARTAMENTO</th>
                                <th style="width:120px;" class="text-center">ASISTENCIA</th>
                                <th style="min-width:280px;">EVENTOS</th>
                                <th style="width:220px;">EVIDENCIA (1 foto)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($personas as $p)
                                @php
                                    $sel = $selectedByPerson[$p->id] ?? [];
                                    $asist = $asistenciaMap[$p->id] ?? [];
                                    $just = $justMap[$p->id] ?? [];
                                    $deptName = $p->departamento ? $p->departamento->descripcion : '';
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge badge-primary" style="border-radius:10px; padding:8px 10px;">{{ $p->identificacion ?: ('#EMP'.$p->id) }}</span>
                                    </td>
                                    <td>{{ $p->nombre_completo }}</td>
                                    <td>{{ $deptName }}</td>
                                    <td class="text-center">
                                        <input type="checkbox" class="js-tgl" data-persona="{{ $p->id }}" {{ !empty($sel) ? 'checked' : '' }} />
                                    </td>
                                    <td>
                                        <select name="person_events[{{ $p->id }}][]" class="form-control js-eventos" multiple data-persona="{{ $p->id }}">
                                            @foreach(($eventsByPerson[$p->id] ?? []) as $e)
                                                @php
                                                    $isSel = in_array($e->id, $sel, true);
                                                    $badge = '';
                                                    if (!empty($asist[$e->id]) && ($asist[$e->id]->estado_asistencia ?? null) === 'A') $badge = ' (A)';
                                                    elseif (!empty($asist[$e->id]) && ($asist[$e->id]->estado_asistencia ?? null) === 'F') $badge = ' (F)';
                                                    elseif (!empty($just[$e->id])) $badge = ' (JUSTIFICÓ)';
                                                @endphp
                                                <option value="{{ $e->id }}" {{ $isSel ? 'selected' : '' }}>{{ $e->titulo }}{{ $badge }}</option>
                                            @endforeach
                                        </select>
                                        @if(empty($eventsByPerson[$p->id] ?? []))
                                            <small class="text-muted">Sin eventos para esta fecha.</small>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="file" name="person_file[{{ $p->id }}]" class="form-control" accept="image/*,.pdf,.doc,.docx" />
                                        <small class="text-muted">Se aplica a los eventos seleccionados.</small>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No hay empleados para el filtro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@section('footer')
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('vendor/flatpickr/l10n/es.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/select2/js/i18n/es.js') }}"></script>
    <script>
        $(function(){
            var hasEventos = {{ (($events ?? collect())->count() > 0) ? 'true' : 'false' }};

            // Fecha en formato visual dd/mm/aaaa, valor real yyyy-mm-dd
            if (window.flatpickr) {
                flatpickr('#fecha', {
                    locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true
                });
            }

            // Empresas con búsqueda
            $('#empresa_id').select2({ width:'100%', placeholder:'-- General (todas) --', allowClear:true, language:'es' });

            // Persona combobox (ajax)
            $('#persona_id').select2({
                width: '100%',
                placeholder: 'Buscar por nombre o identificación',
                allowClear: true,
                minimumInputLength: 2,
                language: 'es',
                ajax: {
                    url: '{{ route('PgAsistenciasEmpresaPersonasSearch') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            empresa_id: $('#empresa_id').val() || ''
                        };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });

            $('.js-eventos').select2({ width:'100%', language:'es' });

            function noEventosMsg(){
                alert('No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.');
            }

            // Bloquea acciones si no hay eventos para la fecha
            $('#btnCerrarDia, #btnActualizar').on('click', function(e){
                if (!hasEventos) {
                    e.preventDefault();
                    e.stopPropagation();
                    noEventosMsg();
                    return false;
                }
            });

            $('#chkAutoSave').on('change.noEventos', function(){
                if ($(this).is(':checked') && !hasEventos) {
                    $(this).prop('checked', false);
                    $('#auto_close').val('0');
                    noEventosMsg();
                }
            });

            $('.js-tgl').on('change', function(){
                var pid = $(this).data('persona');
                var $sel = $('.js-eventos[data-persona="'+pid+'"]');
                if ($(this).is(':checked')) {
                    // seleccionar todo
                    $sel.find('option').prop('selected', true);
                } else {
                    $sel.val(null);
                }
                $sel.trigger('change');
            });

            // Marcar general (selecciona todo/limpia en todas las filas)
            $('#chkGeneral').on('change', function(){
                var mark = $(this).is(':checked');
                $('.js-tgl').each(function(){
                    $(this).prop('checked', mark).trigger('change');
                });
            });

            // Auto-actualizar: guarda por persona cuando cambia selección (sin evidencias)
            function savePersona(pid){
                var $sel = $('.js-eventos[data-persona="'+pid+'"]').first();
                var eventos = $sel.val() || [];
                return $.ajax({
                    method: 'POST',
                    url: '{{ route('PgAsistenciasEmpresaActualizarItem') }}',
                    data: {
                        _token: '{{ csrf_token() }}',
                        fecha: '{{ $fecha }}',
                        empresa_id: '{{ $empresaId }}',
                        persona_id: pid,
                        eventos: eventos,
                        auto_close: $('#chkAutoSave').is(':checked') ? '1' : '0'
                    }
                });
            }

            var saveTimer = {};
            function debounceSave(pid){
                if (!$('#chkAutoSave').is(':checked')) return;
                if (saveTimer[pid]) clearTimeout(saveTimer[pid]);
                saveTimer[pid] = setTimeout(function(){
                    savePersona(pid).fail(function(xhr){
                        console.error('Auto-actualizar falló', xhr);
                    });
                }, 400);
            }

            // Mantener hidden auto_close para el submit normal
            $('#chkAutoSave').on('change', function(){
                $('#auto_close').val($(this).is(':checked') ? '1' : '0');
            }).trigger('change');

            $('.js-eventos').on('change', function(){
                debounceSave($(this).data('persona'));
            });
            $('.js-tgl').on('change', function(){
                debounceSave($(this).data('persona'));
            });
        });
    </script>
@endsection
