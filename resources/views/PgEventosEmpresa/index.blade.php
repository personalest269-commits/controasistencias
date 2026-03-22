@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fullcalendar/main.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">

    <style>
        :root{
            --pg-green:#28a745;
            --pg-green-dark:#218838;
            --pg-border:#e9ecef;
            --pg-muted:#6c757d;
        }

        .pg-card{
            border:0;
            border-radius:12px;
            box-shadow: 0 10px 25px rgba(0,0,0,.06);
        }
        .pg-card .card-header{
            background:#fff;
            border-bottom:1px solid var(--pg-border);
            border-top-left-radius:12px;
            border-top-right-radius:12px;
        }
        .pg-section-title{
            font-size:16px;
            font-weight:600;
            margin:0;
            color:#111827;
        }

        /* FullCalendar look (verde como en las capturas) */
        .fc .fc-toolbar-title{
            font-size:28px;
            font-weight:700;
            letter-spacing:.5px;
            text-transform:uppercase;
        }
        .fc .fc-button{
            background:var(--pg-green);
            border-color:var(--pg-green);
            box-shadow:none !important;
            text-transform:capitalize;
        }
        .fc .fc-button:hover,
        .fc .fc-button:focus{
            background:var(--pg-green-dark);
            border-color:var(--pg-green-dark);
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active{
            background:var(--pg-green-dark);
            border-color:var(--pg-green-dark);
        }
        .fc .fc-today-button{
            background:var(--pg-green) !important;
            border-color:var(--pg-green) !important;
        }
        .fc .fc-col-header-cell-cushion{
            color:#111827;
            font-weight:600;
        }
        .fc .fc-daygrid-day-number{
            color:#111827;
            font-weight:500;
            font-size:12px;
        }

        /* Upcoming list */
        .upcoming-item{
            border:1px solid var(--pg-border);
            border-radius:12px;
            padding:12px;
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            background:#fff;
        }
        .upcoming-dot{
            width:10px; height:10px;
            border-radius:999px;
            margin-top:6px;
            flex:0 0 auto;
            background:var(--pg-green);
        }
        .upcoming-title{
            font-weight:600;
            margin:0;
            line-height:1.2;
            cursor:pointer;
        }
        .upcoming-meta{
            font-size:12px;
            color:var(--pg-muted);
            margin-top:4px;
        }
        .upcoming-actions .btn{
            border-radius:10px;
            padding:6px 10px;
        }

        /* Modal styles */
        .modal .modal-content{
            border-radius:14px;
        }
        .color-pill{
            width:26px; height:26px;
            border-radius:8px;
            border:2px solid transparent;
            cursor:pointer;
            display:inline-block;
        }
        .color-pill.active{ border-color:#111827; }
        .select2-container .select2-selection--multiple{
            min-height:38px;
            border-radius:10px;
            border:1px solid #ced4da;
        }
    </style>
@endsection

@section('content')
@php
    // Para eventos queremos fecha + hora. Usamos input datetime-local (nativo) si el formato base es ISO.
    // Igual aceptamos/validamos en backend con FORMATO_FECHA.
    $useNativeDateTime = true;
    $pg_datetime_placeholder = $pg_date_placeholder ?? (\App\Models\PgConfiguracion::placeholderFecha());
@endphp
@section('title', 'Gestión de Eventos (Empresas)')

@section('content_header')
    <h1>Gestión de Eventos</h1>
    <p class="text-muted mb-0">Los eventos se muestran siempre por título. Puedes crear, modificar y eliminar (eliminación lógica).</p>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Calendario</h3>
                <button class="btn btn-success" id="btnNuevo" title="Nuevo"><i class="fas fa-plus"></i></button>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Próximos eventos</h3>
                <button class="btn btn-success" id="btnNuevo2" title="Nuevo"><i class="fas fa-plus"></i></button>
            </div>
            <div class="card-body" id="upcomingList">
                @if(empty($upcoming) || count($upcoming)===0)
                    <div class="text-muted">Sin eventos próximos.</div>
                @else
                    <div class="d-grid" style="gap:12px;">
                        @foreach($upcoming as $e)
                            @php
                                $dot = (!empty($e->color) ? $e->color : '#28a745');
                                $start = $e->fecha_inicio ? \Carbon\Carbon::parse($e->fecha_inicio)->format('d/m/Y H:i:s') : null;
                                $end = $e->fecha_fin ? \Carbon\Carbon::parse($e->fecha_fin)->format('d/m/Y H:i:s') : null;
                            @endphp
                            <div class="upcoming-item" data-id="{{ $e->id }}">
                                <div class="d-flex align-items-start" style="gap:10px;">
                                    <span class="upcoming-dot" style="background:{{ $dot }}"></span>
                                    <div>
                                        <p class="upcoming-title js-open" data-id="{{ $e->id }}">{{ $e->titulo }}</p>
                                        <div class="upcoming-meta">
                                            Fecha inicio: {{ $start }}<br>
                                            Fecha fin: {{ $end }}
                                        </div>
                                    </div>
                                </div>
                                <div class="upcoming-actions d-flex" style="gap:8px;">
                                    <button class="btn btn-info btn-sm js-edit" title="Editar" data-id="{{ $e->id }}"><i class="fas fa-pen"></i></button>
                                    <button class="btn btn-danger btn-sm js-del" title="Eliminar" data-id="{{ $e->id }}"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Evento (Empresas) -->
<div class="modal fade" id="eventoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Crear nuevo evento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="eventoId" />

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Empresa<span class="text-danger">*</span></label>
                            <select id="empresas" class="form-control" multiple>
                                <option value="__ALL__">Todos</option>
                                @foreach($empresas as $e)
                                    <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Selecciona <b>Todos</b> o una/varias empresas.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Empleado<span class="text-danger">*</span></label>
                            <select id="personas" class="form-control" multiple>
                                <option value="__ALL__">Todos</option>
                                @foreach($personas as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre_completo }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Selecciona <b>Todos</b> o uno/varios empleados.</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Título del evento<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="titulo" placeholder="Ingresa el título del evento">
                    <div class="invalid-feedback" id="errTitulo"></div>
                </div>

                @php
                    // Reutilizamos el placeholder configurado si existe.
                    $pg_datetime_placeholder = $pg_datetime_placeholder ?? 'dd/mm/aaaa HH:MM:SS';
                @endphp

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fecha inicio<span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="fechaInicio" placeholder="{{ $pg_datetime_placeholder }}" autocomplete="off">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="far fa-calendar"></i></span>
                                </div>
                            </div>
                            <div class="invalid-feedback" id="errFechaInicio"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fecha fin<span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="fechaFin" placeholder="{{ $pg_datetime_placeholder }}" autocomplete="off">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="far fa-calendar"></i></span>
                                </div>
                            </div>
                            <div class="invalid-feedback" id="errFechaFin"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Color del evento</label>
                    <div class="d-flex" style="gap:8px;">
                        <span class="color-pill active" data-color="#17a2b8" style="background:#17a2b8"></span>
                        <span class="color-pill" data-color="#fd7e14" style="background:#fd7e14"></span>
                        <span class="color-pill" data-color="#e83e8c" style="background:#e83e8c"></span>
                        <span class="color-pill" data-color="#28a745" style="background:#28a745"></span>
                        <span class="color-pill" data-color="#6f42c1" style="background:#6f42c1"></span>
                    </div>
                    <input type="hidden" id="color" value="#17a2b8">
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea class="form-control" id="descripcion" rows="4" placeholder="Ingrese la descripción"></textarea>
                </div>

                <div class="alert alert-danger d-none" id="errGeneral"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardar">Crear</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fullcalendar/main.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <style>
        .color-pill{width:18px;height:18px;border-radius:999px;display:inline-block;cursor:pointer;border:2px solid transparent;}
        .color-pill.active{border-color:#00000033;}
        .upcoming-item{border:1px solid #e5e7eb;border-radius:12px;padding:10px;}
        .upcoming-title{font-weight:700;margin:0;cursor:pointer;}
        .upcoming-meta{font-size:12px;color:#6b7280;}
        .upcoming-dot{width:10px;height:10px;border-radius:999px;display:inline-block;margin-top:5px;}
    </style>
@endsection

@section('footer')
    <script src="{{ asset('admin_lte/plugins/fullcalendar/main.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/fullcalendar/locales/es.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('vendor/flatpickr/l10n/es.js') }}"></script>

    <script>
        (function(){
            const CSRF = '{{ csrf_token() }}';
            const USE_NATIVE_DATETIME = false;
            const USE_NATIVE_DATE = USE_NATIVE_DATETIME;

            function notifyErr(title, text){
                if (window.Swal && Swal.fire) return Swal.fire(title || 'Error', text || '', 'error');
                alert((title ? title + ': ' : '') + (text || ''));
            }
            function safeSwalFire(options){
                try{ if(window.Swal && typeof Swal.fire === 'function') return Swal.fire(options); }catch(e){}
                if(options && options.showCancelButton){
                    const ok = confirm((options.title ? options.title + "\n" : "") + (options.text || ""));
                    return Promise.resolve({isConfirmed: ok});
                }
                alert((options.title ? options.title + "\n" : "") + (options.text || ""));
                return Promise.resolve({isConfirmed: true});
            }

            const ROUTES = {
                feed: '{{ route('PgEventosFeed') }}',
                upcoming: '{{ route('PgEventosUpcoming') }}',
                get: (id) => '{{ url('/admin/PgEventosEmpresa/obtener') }}/' + id,
                store: '{{ route('PgEventosEmpresaStore') }}',
                update: (id) => '{{ url('/admin/PgEventosEmpresa/actualizar') }}/' + id,
                del: (id) => '{{ url('/admin/PgEventosEmpresa/eliminar') }}/' + id,
            };

            let cal = null;
            let editingId = null;

            function resetErrors(){
                $('#errGeneral').addClass('d-none').text('');
                $('#titulo').removeClass('is-invalid');
                $('#fechaInicio').removeClass('is-invalid');
                $('#fechaFin').removeClass('is-invalid');
                if(window.fpInicio && window.fpInicio.altInput){ $(window.fpInicio.altInput).removeClass('is-invalid'); }
                if(window.fpFin && window.fpFin.altInput){ $(window.fpFin.altInput).removeClass('is-invalid'); }
                $('#errTitulo').text('');
                $('#errFechaInicio').text('');
                $('#errFechaFin').text('');
            }

            function ensureAllBehavior($sel){
                $sel.on('select2:select', function(e){
                    const id = e.params.data.id;
                    let vals = $sel.val() || [];
                    if(id === '__ALL__'){
                        $sel.val(['__ALL__']).trigger('change.select2');
                    } else {
                        if(vals.includes('__ALL__')){
                            vals = vals.filter(v => v !== '__ALL__');
                            $sel.val(vals).trigger('change.select2');
                        }
                    }
                });
                $sel.on('select2:unselect', function(){
                    const vals = $sel.val() || [];
                    if(vals.length === 0){
                        $sel.val(['__ALL__']).trigger('change.select2');
                    }
                });
            }

            function setColor(hex){
                $('#color').val(hex);
                $('.color-pill').removeClass('active');
                $('.color-pill').each(function(){
                    if($(this).data('color') === hex){ $(this).addClass('active'); }
                });
            }

            function applyDateRules(isCreate){
                // Mantiene la misma lógica que PgEventos (sin depender de departamentos)
                const now = new Date();
                const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);

                if(window.fpInicio && window.fpFin){
                    window.fpInicio.set('maxDate', todayStart);
                    const startDate = window.fpInicio.selectedDates && window.fpInicio.selectedDates[0] ? window.fpInicio.selectedDates[0] : todayStart;
                    window.fpFin.set('minDate', startDate);
                    if(isCreate && startDate){
                        const end = new Date(startDate.getTime() + 60*60*1000);
                        window.fpFin.setDate(end, false);
                    }
                }
            }

            function openCreate(dateStr){
                editingId = null;
                $('#eventoId').val('');
                $('#modalTitle').text('Crear nuevo evento');
                $('#btnGuardar').text('Crear');

                $('#titulo').val('');
                $('#descripcion').val('');

                $('#empresas').val(['__ALL__']).trigger('change');
                $('#personas').val(['__ALL__']).trigger('change');

                if(dateStr){
                    const v = (String(dateStr).length === 10) ? (dateStr + ' 00:00:00') : dateStr;
                    if(window.fpInicio){ window.fpInicio.setDate(v, false, 'Y-m-d H:i:S'); }
                    if(window.fpFin){ window.fpFin.setDate(v, false, 'Y-m-d H:i:S'); }
                } else {
                    if(window.fpInicio){ window.fpInicio.clear(); }
                    if(window.fpFin){ window.fpFin.clear(); }
                }

                applyDateRules(true);
                setColor('#17a2b8');
                resetErrors();
                $('#eventoModal').modal('show');
            }

            function openEdit(id){
                editingId = id;
                $('#eventoId').val(id);
                $('#modalTitle').text('Editar evento');
                $('#btnGuardar').text('Actualizar');
                resetErrors();

                $.getJSON(ROUTES.get(id))
                    .done(function(res){
                        $('#titulo').val(res.titulo || '');
                        $('#descripcion').val(res.descripcion || '');
                        if(window.fpInicio){ window.fpInicio.setDate(res.fecha_inicio || null, false, 'Y-m-d H:i:S'); }
                        if(window.fpFin){ window.fpFin.setDate(res.fecha_fin || null, false, 'Y-m-d H:i:S'); }

                        applyDateRules(false);

                        if(res.todos_empresas){
                            $('#empresas').val(['__ALL__']).trigger('change');
                        } else {
                            $('#empresas').val(res.empresas || []).trigger('change');
                        }
                        if(res.todas_personas){
                            $('#personas').val(['__ALL__']).trigger('change');
                        } else {
                            $('#personas').val(res.personas || []).trigger('change');
                        }

                        setColor(res.color || '#17a2b8');
                        $('#eventoModal').modal('show');
                    })
                    .fail(function(){ notifyErr('Error','No se pudo cargar el evento.'); });
            }

            function buildPayload(){
                let emps = $('#empresas').val() || [];
                let pers = $('#personas').val() || [];

                const todos_empresas = emps.includes('__ALL__') || emps.length === 0;
                const todas_personas = pers.includes('__ALL__') || pers.length === 0;

                if(todos_empresas){ emps = []; }
                if(todas_personas){ pers = []; }

                return {
                    _token: CSRF,
                    titulo: $('#titulo').val(),
                    fecha_inicio: $('#fechaInicio').val(),
                    fecha_fin: $('#fechaFin').val(),
                    color: $('#color').val(),
                    descripcion: $('#descripcion').val(),
                    todos_empresas: todos_empresas ? 1 : 0,
                    todas_personas: todas_personas ? 1 : 0,
                    empresas: emps,
                    personas: pers
                };
            }

            function refreshUpcoming(){
                $.getJSON(ROUTES.upcoming)
                    .done(function(list){
                        const $wrap = $('#upcomingList');
                        if(!list || !list.length){
                            $wrap.html('<div class="text-muted">Sin eventos próximos.</div>');
                            return;
                        }
                        let html = '<div class="d-grid" style="gap:12px;">';
                        list.forEach(function(e){
                            const dot = (e.color && e.color.trim()) ? e.color : '#28a745';
                            const start = e.fecha_inicio_display || e.fecha_inicio;
                            const end = e.fecha_fin_display || e.fecha_fin;
                            html += `
                                <div class="upcoming-item" data-id="${e.id}">
                                    <div class="d-flex align-items-start" style="gap:10px;">
                                        <span class="upcoming-dot" style="background:${dot}"></span>
                                        <div>
                                            <p class="upcoming-title js-open" data-id="${e.id}">${e.titulo}</p>
                                            <div class="upcoming-meta">
                                                Fecha inicio: ${start}<br>
                                                Fecha fin: ${end}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="upcoming-actions d-flex" style="gap:8px;">
                                        <button class="btn btn-info btn-sm js-edit" title="Editar" data-id="${e.id}"><i class="fas fa-pen"></i></button>
                                        <button class="btn btn-danger btn-sm js-del" title="Eliminar" data-id="${e.id}"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $wrap.html(html);
                    });
            }

            function doDelete(id){
                safeSwalFire({
                    title: 'Eliminar evento',
                    text: '¿Deseas eliminar este evento?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(function(r){
                    if(!r.isConfirmed) return;
                    $.post(ROUTES.del(id), {_token:CSRF})
                        .done(function(){
                            refreshUpcoming();
                            if(cal){ cal.refetchEvents(); }
                        })
                        .fail(function(){ notifyErr('Error','No se pudo eliminar el evento.'); });
                });
            }

            $(function(){
                // Select2
                $('#empresas').select2({ width:'100%' });
                $('#personas').select2({ width:'100%' });
                ensureAllBehavior($('#empresas'));
                ensureAllBehavior($('#personas'));
                $('#empresas').val(['__ALL__']).trigger('change');
                $('#personas').val(['__ALL__']).trigger('change');

                // Color
                $('.color-pill').on('click', function(){ setColor($(this).data('color')); });

                // Flatpickr
                if(!USE_NATIVE_DATETIME && window.flatpickr){
                    window.fpInicio = flatpickr('#fechaInicio', {
                        enableTime: true,
                        time_24hr: true,
                        dateFormat: 'Y-m-d H:i:S',
                        altInput: true,
                        altFormat: 'd/m/Y H:i:S',
                        locale: 'es'
                    });
                    window.fpFin = flatpickr('#fechaFin', {
                        enableTime: true,
                        time_24hr: true,
                        dateFormat: 'Y-m-d H:i:S',
                        altInput: true,
                        altFormat: 'd/m/Y H:i:S',
                        locale: 'es'
                    });
                }

                // Calendar
                const el = document.getElementById('calendar');
                cal = new FullCalendar.Calendar(el, {
                    locale: 'es',
                    initialView: 'dayGridMonth',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
                    selectable: true,
                    events: ROUTES.feed,
                    dateClick: function(info){ openCreate(info.dateStr); },
                    eventClick: function(info){ openEdit(info.event.id); }
                });
                cal.render();

                // Botones
                $('#btnNuevo, #btnNuevo2').on('click', function(){ openCreate(); });

                // Upcoming actions
                $(document).on('click', '.js-edit, .js-open', function(){
                    openEdit($(this).data('id'));
                });
                $(document).on('click', '.js-del', function(){
                    doDelete($(this).data('id'));
                });

                // Guardar
                $('#btnGuardar').on('click', function(){
                    resetErrors();
                    const payload = buildPayload();
                    const url = editingId ? ROUTES.update(editingId) : ROUTES.store;

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: payload
                    }).done(function(){
                        $('#eventoModal').modal('hide');
                        refreshUpcoming();
                        if(cal){ cal.refetchEvents(); }
                    }).fail(function(xhr){
                        if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                            const e = xhr.responseJSON.errors;
                            if(e.titulo){ $('#titulo').addClass('is-invalid'); $('#errTitulo').text(e.titulo[0]); }
                            if(e.fecha_inicio){ $('#fechaInicio').addClass('is-invalid'); $('#errFechaInicio').text(e.fecha_inicio[0]); }
                            if(e.fecha_fin){ $('#fechaFin').addClass('is-invalid'); $('#errFechaFin').text(e.fecha_fin[0]); }
                            $('#errGeneral').removeClass('d-none').text('Revisa los campos obligatorios.');
                        } else {
                            notifyErr('Error','No se pudo guardar el evento.');
                        }
                    });
                });
            });
        })();
    </script>
@endsection
