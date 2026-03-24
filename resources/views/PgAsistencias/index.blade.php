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
            <h4 class="mb-0">Administrar asistencia masiva</h4>
            <small class="text-muted">Marca asistencia por evento. Si seleccionas un departamento, se pre-seleccionan todos los eventos aplicables por persona.</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('PgAsistenciasIndex') }}" id="filterForm">
        <div class="pg-filter mb-3">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="mb-1">Fecha</label>
                    <input type="text" id="fecha" name="fecha" value="{{ $fecha }}" class="form-control" autocomplete="off" />
                </div>
                <div class="col-md-4">
                    <label class="mb-1">Departamento</label>
                    <select id="departamento_id" name="departamento_id" class="form-control">
                        <option value="">-- General (todos) --</option>
                        @foreach($departamentos as $d)
                            <option value="{{ $d->id }}" {{ ($departamentoId==$d->id)?'selected':'' }}>{{ $d->descripcion }}</option>
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
                <div class="col-md-2">
                    <label class="mb-1">Evento</label>
                    <select id="evento_id" name="evento_id" class="form-control" style="width:100%">
                        <option value="">-- Todos --</option>
                        @foreach(($allEventsDay ?? collect()) as $evFiltro)
                            <option value="{{ $evFiltro->id }}" {{ (($eventoId ?? null) == $evFiltro->id) ? 'selected' : '' }}>{{ $evFiltro->titulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 text-right">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>

            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Si eliges un departamento, las evidencias se cargan 1 vez por evento (máx 4 fotos). En modo general, se puede cargar 1 foto por persona.
                </small>
                <br>
                <small class="text-muted">
                    <i class="fas fa-filter"></i>
                    Al cambiar un filtro (fecha, departamento, persona o evento), el listado se actualiza automáticamente.
                </small>
				  
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('PgAsistenciasActualizar') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="fecha" value="{{ $fecha }}" />
        <input type="hidden" name="departamento_id" value="{{ $departamentoId }}" />
        <input type="hidden" name="evento_id" value="{{ $eventoId ?? '' }}" />

        @if($departamentoId)
            <div class="card pg-card mb-3">
                <div class="card-header">
                    <strong>Evidencias por evento (Departamento)</strong>
                    <span class="text-muted">&nbsp;— máximo 4 fotos por evento (se cargan una sola vez)</span>
                </div>
                <div class="card-body">
                    @if(empty($deptEventRows) || count($deptEventRows)==0)
                        <div class="text-muted">No hay eventos para la fecha seleccionada.</div>
                    @else
                        <div class="row">
                            @foreach($deptEventRows as $row)
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3" style="background:#fff;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <div style="font-weight:600;">{{ $row['evento']->titulo }}</div>
                                                <div class="text-muted" style="font-size:12px;">Evidencias actuales: {{ $row['archivos_count'] }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <input type="file" name="dept_event_files[{{ $row['evento']->id }}][]" class="form-control" multiple accept="image/*" />
                                            <small class="text-muted">Sube 1 a 4 fotos (jpg/png/webp). El sistema valida el máximo total.</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="card pg-card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <strong>Listado de empleados</strong>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        @if(empty($eventoId))
                            <div style="min-width:320px;">
                                <select id="general_events" class="form-control" multiple>
                                    @foreach(($events ?? collect()) as $evGeneral)
                                        <option value="{{ $evGeneral->id }}">{{ $evGeneral->titulo }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="custom-control custom-checkbox mr-2">
                            <input type="checkbox" class="custom-control-input" id="chkGeneral">
                            <label class="custom-control-label" for="chkGeneral">Marcar general</label>
                        </div>
                        <div class="custom-control custom-checkbox mr-2">
                            <input type="checkbox" class="custom-control-input" id="chkAutoSave">
                            <label class="custom-control-label" for="chkAutoSave">Auto-actualizar</label>
                        </div>
                        <input type="hidden" name="auto_close" id="auto_close" value="0" />
                        <a href="{{ route('PgAsistenciasReportes') }}" class="btn btn-warning btn-sm" id="btnReportes">Reportes</a>
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
                                @if(empty($eventoId))
                                    <th style="min-width:280px;">EVENTOS</th>
                                @endif
                                @if(!$departamentoId)
                                    <th style="width:220px;">EVIDENCIA (1 foto)</th>
                                @endif
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
                                    @if(!empty($eventoId))
                                        <input type="hidden" name="person_events[{{ $p->id }}][]" class="js-eventos-hidden" data-persona="{{ $p->id }}" value="{{ $eventoId }}" />
                                    @else
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
                                    @endif
                                    @if(!$departamentoId)
                                        <td>
                                            <input type="file" name="person_file[{{ $p->id }}]" class="form-control" accept="image/*,.pdf,.doc,.docx" />
                                            <small class="text-muted">Se aplica a los eventos seleccionados.</small>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                @php
                                    $cols = 4 + (empty($eventoId) ? 1 : 0) + (!$departamentoId ? 1 : 0);
                                @endphp
                                <tr><td colspan="{{ $cols }}" class="text-muted">No hay empleados para el filtro.</td></tr>
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
                    allowInput: true,
                    onClose: function() {
                        $('#filterForm').trigger('submit');
                    }
                });
            }

            // Departamentos con búsqueda
            $('#departamento_id').select2({ width:'100%', placeholder:'-- General (todos) --', allowClear:true, language:'es' });
            $('#evento_id').select2({ width:'100%', placeholder:'-- Todos --', allowClear:true, language:'es' });

            // Persona combobox (ajax)
            $('#persona_id').select2({
                width: '100%',
                placeholder: 'Buscar por nombre o identificación',
                allowClear: true,
                minimumInputLength: 2,
                language: 'es',
                ajax: {
                    url: '{{ route('PgAsistenciasPersonasSearch') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            departamento_id: $('#departamento_id').val() || ''
                        };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });

            $('.js-eventos').select2({
                width:'100%',
                language:'es',
                placeholder: 'Seleccione eventos del día',
                closeOnSelect: false
            });

            function noEventosMsg(){
                alert('No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.');
            }

            // Bloquea acciones si no hay eventos para la fecha
            $('#btnActualizar').on('click', function(e){
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
                var $hidden = $('.js-eventos-hidden[data-persona="'+pid+'"]');
                var $sel = $('.js-eventos[data-persona="'+pid+'"]');

                if ($hidden.length) {
                    $hidden.prop('disabled', !$(this).is(':checked'));
                } else if ($sel.length) {
                    if ($(this).is(':checked')) {
                        $sel.find('option').prop('selected', true);
                    } else {
                        $sel.val(null);
                    }
                    $sel.trigger('change');
                }

                debounceSave(pid, true);
            });

            // Marcar general (selecciona todo/limpia en todas las filas)
            $('#chkGeneral').on('change', function(){
                var mark = $(this).is(':checked');
                $('.js-tgl').each(function(){
                    $(this).prop('checked', mark).trigger('change');
                });
            });

            // Modo general de eventos:
            // - lo seleccionado aquí se replica en cada fila (si el evento aplica a la persona)
            // - si se quita del modo general, también se quita en cada fila
            var lastGeneralEvents = [];
            $('#general_events').on('change', function(){
                var selectedGeneral = ($(this).val() || []).map(String);
                var removedGeneral = lastGeneralEvents.filter(function(id){
                    return selectedGeneral.indexOf(String(id)) === -1;
                });

                $('.js-eventos').each(function(){
                    var $rowSelect = $(this);
                    var current = ($rowSelect.val() || []).map(String);

                    // Agregar los eventos seleccionados en modo general que existan en esta fila
                    selectedGeneral.forEach(function(eid){
                        var $opt = $rowSelect.find('option[value="'+eid+'"]');
                        var isLocked = String($opt.data('locked')) === '1';
                        if ($opt.length && !isLocked && current.indexOf(eid) === -1) {
                            current.push(eid);
                        }
                    });

                    // Quitar los eventos removidos en modo general
                    removedGeneral.forEach(function(eid){
                        var $opt = $rowSelect.find('option[value="'+eid+'"]');
                        var isLocked = String($opt.data('locked')) === '1';
                        if (!isLocked) {
                            current = current.filter(function(v){ return String(v) !== String(eid); });
                        }
                    });

                    $rowSelect.val(current).trigger('change');
                });

                lastGeneralEvents = selectedGeneral.slice();
            });

            // Auto-actualizar: guarda por persona cuando cambia selección (sin evidencias)
            function savePersona(pid){
                var $hidden = $('.js-eventos-hidden[data-persona="'+pid+'"]');
                var $sel = $('.js-eventos[data-persona="'+pid+'"]').first();
                var eventos = $sel.length ? ($sel.val() || []) : [];
                if ($hidden.length) {
                    eventos = [];
                    $hidden.each(function(){
                        if (!$(this).prop('disabled')) eventos.push($(this).val());
                    });
                }
                return $.ajax({
                    method: 'POST',
                    url: '{{ route('PgAsistenciasActualizarItem') }}',
                    data: {
                        _token: '{{ csrf_token() }}',
                        fecha: '{{ $fecha }}',
                        departamento_id: '{{ $departamentoId }}',
                        evento_id: '{{ $eventoId ?? '' }}',
                        persona_id: pid,
                        eventos: eventos,
                        auto_close: $('#chkAutoSave').is(':checked') ? '1' : '0'
                    }
                });
            }

            var saveTimer = {};
            function debounceSave(pid, force){
                if (!force && !$('#chkAutoSave').is(':checked')) return;
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
            });

            // Por defecto queda desmarcado; el usuario decide si activa auto-actualizar.
            $('#chkAutoSave').prop('checked', false).trigger('change');

            $('.js-eventos-hidden').each(function(){
                var pid = $(this).data('persona');
                var isChecked = $('.js-tgl[data-persona="'+pid+'"]').is(':checked');
                $(this).prop('disabled', !isChecked);
            });
            $('.js-eventos').on('change', function(){
                debounceSave($(this).data('persona'), true);
            });

            // Auto-filtrar al cambiar filtros principales
            $('#departamento_id, #evento_id').on('change', function(){
                $('#filterForm').trigger('submit');
            });
            $('#persona_id').on('select2:select select2:clear', function(){
                $('#filterForm').trigger('submit');
            });
        });
    </script>
@endsection
