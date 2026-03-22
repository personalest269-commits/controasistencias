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
<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Gestión de Eventos</h4>
            <small class="text-muted">Los eventos se muestran siempre por título. Puedes crear, modificar y eliminar (eliminación lógica).</small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-9">
            <div class="card pg-card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="pg-section-title">Calendario</h3>
                        <button class="btn btn-success btn-sm" id="btnNuevoTop">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card pg-card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="pg-section-title">Próximos eventos</h3>
                        <button class="btn btn-success btn-sm" id="btnNuevo">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" id="upcomingList">
                    @if(!empty($upcoming) && count($upcoming))
                        <div class="d-grid" style="gap:12px;">
                            @foreach($upcoming as $e)
                                <div class="upcoming-item" data-id="{{ $e->id }}">
                                    <div class="d-flex align-items-start" style="gap:10px;">
                                        <span class="upcoming-dot" style="background:{{ $e->color ?: '#28a745' }}"></span>
                                        <div>
                                            <p class="upcoming-title js-open" data-id="{{ $e->id }}">{{ $e->titulo }}</p>
                                            <div class="upcoming-meta">
                                                Fecha inicio: {{ \Carbon\Carbon::parse($e->fecha_inicio)->format('d/m/Y H:i:s') }}<br>
                                                Fecha fin: {{ \Carbon\Carbon::parse($e->fecha_fin)->format('d/m/Y H:i:s') }}
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
                    @else
                        <div class="text-muted">Sin eventos próximos.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar -->
<div class="modal fade" id="eventoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Crear nuevo evento</h5>
                <div class="ml-auto">
                    <button type="button" class="btn btn-success btn-sm" id="btnGenerateAi">
                        <i class="fas fa-magic"></i> Generar con IA
                    </button>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="eventoId" value="">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Departamento<span class="text-danger">*</span></label>
                            <select id="departamentos" class="form-control" multiple>
                                <option value="__ALL__">Todos</option>
                                @foreach($departamentos as $d)
                                    <option value="{{ $d->id }}">{{ $d->descripcion }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Selecciona <b>Todos</b> o uno/varios departamentos.</small>
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
            // Usamos Flatpickr para forzar el formato dd/mm/yyyy en pantalla.
            const USE_NATIVE_DATETIME = false;
            const USE_NATIVE_DATE = USE_NATIVE_DATETIME; // compat
            const DATE_FMT = '{{ $pg_date_format ?? 'Y-m-d H:i:s' }}';

            // En algunos setups (especialmente con Gentelella/Bootstrap3) es más estable enviar el token también en header.
            try {
                $.ajaxSetup({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || CSRF }
                });
            } catch (e) {}

            function notifyOk(title, text){
                if (window.Swal && Swal.fire) return Swal.fire(title || 'OK', text || '', 'success');
                alert((title ? title + ': ' : '') + (text || ''));
            }
            function notifyErr(title, text){
                if (window.Swal && Swal.fire) return Swal.fire(title || 'Error', text || '', 'error');
                alert((title ? title + ': ' : '') + (text || ''));
            }
            const ROUTES = {
                feed: '{{ route('PgEventosFeed') }}',
                upcoming: '{{ route('PgEventosUpcoming') }}',
                get: (id) => '{{ url('/admin/PgEventos/obtener') }}/' + id,
                store: '{{ route('PgEventosStore') }}',
                update: (id) => '{{ url('/admin/PgEventos/actualizar') }}/' + id,
                del: (id) => '{{ url('/admin/PgEventos/eliminar') }}/' + id,
            };

            let cal = null;
            let editingId = null;

            function safeSwalFire(options){
                try{
                    if(window.Swal && typeof Swal.fire === 'function'){
                        return Swal.fire(options);
                    }
                }catch(e){}
                if(options && options.showCancelButton){
                    const ok = confirm((options.title ? options.title + "\n" : "") + (options.text || ""));
                    return Promise.resolve({isConfirmed: ok});
                }
                alert((options.title ? options.title + "\n" : "") + (options.text || ""));
                return Promise.resolve({isConfirmed: true});
            }

            function notify(icon, title, text){
                if (window.Swal && Swal.fire) {
                    return Swal.fire(title || 'Info', text || '', icon || 'info');
                }
                alert((title || 'Info') + (text ? ("\n" + text) : ''));
                return Promise.resolve();
            }

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
                    // si queda vacío, volvemos a All
                    const vals = $sel.val() || [];
                    if(vals.length === 0){
                        $sel.val(['__ALL__']).trigger('change.select2');
                    }
                });
            }

            function openCreate(dateStr){
                editingId = null;
                $('#eventoId').val('');
                $('#modalTitle').text('Crear nuevo evento');
                $('#btnGuardar').text('Crear');

                $('#titulo').val('');
                $('#descripcion').val('');

                // default All
                $('#departamentos').val(['__ALL__']).trigger('change');
                $('#personas').val(['__ALL__']).trigger('change');

                if(dateStr){
                    // FullCalendar puede enviar solo "YYYY-MM-DD".
                    const v = (String(dateStr).length === 10) ? (dateStr + ' 00:00:00') : dateStr;
                    if(window.fpInicio){ window.fpInicio.setDate(v, false, 'Y-m-d H:i:S'); }
                    if(window.fpFin){ window.fpFin.setDate(v, false, 'Y-m-d H:i:S'); }
                } else {
                    if(window.fpInicio){ window.fpInicio.clear(); }
                    if(window.fpFin){ window.fpFin.clear(); }
                }

                // Reglas de fechas (crear):
                // - Inicio: no permitir fechas posteriores (máx hoy)
                // - Fin: no permitir fechas anteriores (mín hoy / y mínimo el inicio)
                // - Fin se autocompleta con inicio + 1 hora
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
                        if(window.fpInicio){
                            window.fpInicio.setDate(res.fecha_inicio || null, false, 'Y-m-d H:i:S');
                        } else {
                            $('#fechaInicio').val(res.fecha_inicio || '');
                        }
                        if(window.fpFin){
                            window.fpFin.setDate(res.fecha_fin || null, false, 'Y-m-d H:i:S');
                        } else {
                            $('#fechaFin').val(res.fecha_fin || '');
                        }

                        // Reaplicar reglas/limites de fechas al editar
                        applyDateRules(false);

                        // Departamentos
                        if(res.todos_departamentos){
                            $('#departamentos').val(['__ALL__']).trigger('change');
                        } else {
                            $('#departamentos').val(res.departamentos || []).trigger('change');
                        }
                        // Personas
                        if(res.todas_personas){
                            $('#personas').val(['__ALL__']).trigger('change');
                        } else {
                            $('#personas').val(res.personas || []).trigger('change');
                        }

                        setColor(res.color || '#17a2b8');
                        $('#eventoModal').modal('show');
                    })
                    .fail(function(){
                        notifyErr('Error','No se pudo cargar el evento.');
                    });
            }

            function setColor(hex){
                $('#color').val(hex);
                $('.color-pill').removeClass('active');
                $('.color-pill').each(function(){
                    if($(this).data('color') === hex){
                        $(this).addClass('active');
                    }
                });
            }

            function buildPayload(){
                let deps = $('#departamentos').val() || [];
                let pers = $('#personas').val() || [];

                const todos_departamentos = deps.includes('__ALL__') || deps.length === 0;
                const todas_personas = pers.includes('__ALL__') || pers.length === 0;

                if(todos_departamentos){ deps = []; }
                if(todas_personas){ pers = []; }

                return {
                    _token: CSRF,
                    titulo: $('#titulo').val(),
                    fecha_inicio: $('#fechaInicio').val(),
                    fecha_fin: $('#fechaFin').val(),
                    color: $('#color').val(),
                    descripcion: $('#descripcion').val(),
                    todos_departamentos: todos_departamentos ? 1 : 0,
                    todas_personas: todas_personas ? 1 : 0,
                    departamentos: deps,
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
                    text: '¿Deseas eliminar este evento? (eliminación lógica)',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((r)=>{
                    if(!r.isConfirmed) return;
                    $.post(ROUTES.del(id), {_token: CSRF})
                        .done(function(res){
                            notifyOk('OK', res.message || 'Evento eliminado.');
                            if(cal){ cal.refetchEvents(); }
                            refreshUpcoming();
                        })
                        .fail(function(){
                            notifyErr('Error','No se pudo eliminar.');
                        });
                });
            }

            $(function(){
                // ============================
                // Reglas de Fechas (Flatpickr)
                // - Mostrar: d/m/Y H:i:S
                // - Enviar/guardar: Y-m-d H:i:S
                // ============================
                const FP_SEND_FMT = 'Y-m-d H:i:S';
                const FP_SHOW_FMT = 'd/m/Y H:i:S';

                function startOfToday(){
                    const n = new Date();
                    return new Date(n.getFullYear(), n.getMonth(), n.getDate(), 0, 0, 0);
                }
                function endOfToday(){
                    const n = new Date();
                    return new Date(n.getFullYear(), n.getMonth(), n.getDate(), 23, 59, 59);
                }

                // Instancias Flatpickr
                const fpInicio = (window.flatpickr ? flatpickr('#fechaInicio', {
                    enableTime: true,
                    enableSeconds: true,
                    time_24hr: false,
                    allowInput: true,
                    locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                    dateFormat: FP_SEND_FMT,
                    altInput: true,
                    altFormat: FP_SHOW_FMT,
                    onChange: function(){
                        applyDateRules(true);
                    }
                }) : null);

                const fpFin = (window.flatpickr ? flatpickr('#fechaFin', {
                    enableTime: true,
                    enableSeconds: true,
                    time_24hr: false,
                    allowInput: true,
                    locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                    dateFormat: FP_SEND_FMT,
                    altInput: true,
                    altFormat: FP_SHOW_FMT,
                    onChange: function(){
                        applyDateRules(false);
                    }
                }) : null);

                // Exponer instancias para openCreate/openEdit
                window.fpInicio = fpInicio;
                window.fpFin = fpFin;

                // Exponemos para usarlo en openCreate/openEdit
                window.applyDateRules = function(isCreate){
                    if(!fpInicio || !fpFin) return;

                    // Inicio: no permitir posteriores (max hoy)
                    fpInicio.set('maxDate', endOfToday());

                    // Fin: no permitir anteriores (min hoy). Además, nunca antes del inicio.
                    const minToday = startOfToday();
                    const iniVal = fpInicio.selectedDates && fpInicio.selectedDates[0] ? fpInicio.selectedDates[0] : null;
                    const minFin = iniVal ? iniVal : minToday;
                    fpFin.set('minDate', minFin);

                    // Si es crear, o si el fin está vacío/incorrecto, autocompletar fin = inicio + 1h
                    if(iniVal){
                        const finVal = fpFin.selectedDates && fpFin.selectedDates[0] ? fpFin.selectedDates[0] : null;
                        const needAuto = isCreate || !finVal || (finVal.getTime() < minFin.getTime());
                        if(needAuto){
                            const auto = new Date(iniVal.getTime() + (60 * 60 * 1000));
                            // No dispares eventos para evitar bucle; luego ajusta reglas.
                            fpFin.setDate(auto, false);
                        }
                    }
                };

                // Inicial (por si el navegador cachea valores)
                applyDateRules(false);

                // Select2
                $('#departamentos').select2({ width:'100%' });
                $('#personas').select2({ width:'100%' });
                ensureAllBehavior($('#departamentos'));
                ensureAllBehavior($('#personas'));

                // Default selection
                $('#departamentos').val(['__ALL__']).trigger('change');
                $('#personas').val(['__ALL__']).trigger('change');

                // Color picker
                $('.color-pill').on('click', function(){
                    setColor($(this).data('color'));
                });

                // New buttons
                $('#btnNuevo, #btnNuevoTop').on('click', function(){ openCreate(null); });

                // Upcoming clicks (delegated)
                $(document).on('click', '.js-open, .js-edit', function(){
                    const id = $(this).data('id');
                    openEdit(id);
                });
                $(document).on('click', '.js-del', function(){
                    const id = $(this).data('id');
                    doDelete(id);
                });

                // Calendar
                const el = document.getElementById('calendar');
                cal = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    height: 'auto',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: {
                        today: 'hoy',
                        month: 'mes',
                        week: 'semana',
                        day: 'día'
                    },
                    events: ROUTES.feed,
                    dateClick: function(info){
                        openCreate(info.dateStr);
                    },
                    eventClick: function(info){
                        openEdit(info.event.id);
                    }
                });
                cal.render();

                // Save
                $('#btnGuardar').on('click', function(){
                    resetErrors();
                    const payload = buildPayload();

                    const url = editingId ? ROUTES.update(editingId) : ROUTES.store;

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: payload
                    }).done(function(res){
                        $('#eventoModal').modal('hide');
                        if(cal){ cal.refetchEvents(); }
                        refreshUpcoming();
                        notifyOk('OK', res.message || 'Guardado.');
                    }).fail(function(xhr){
                        if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                            const errs = xhr.responseJSON.errors;
                            if(errs.titulo){ $('#titulo').addClass('is-invalid'); $('#errTitulo').text(errs.titulo[0]); }
                            if(errs.fecha_inicio){
                                $('#fechaInicio').addClass('is-invalid');
                                if(window.fpInicio && window.fpInicio.altInput){ $(window.fpInicio.altInput).addClass('is-invalid'); }
                                $('#errFechaInicio').text(errs.fecha_inicio[0]);
                            }
                            if(errs.fecha_fin){
                                $('#fechaFin').addClass('is-invalid');
                                if(window.fpFin && window.fpFin.altInput){ $(window.fpFin.altInput).addClass('is-invalid'); }
                                $('#errFechaFin').text(errs.fecha_fin[0]);
                            }
                            $('#errGeneral').removeClass('d-none').text('Revisa los campos obligatorios.');
                        } else {
                            $('#errGeneral').removeClass('d-none').text('No se pudo guardar el evento.');
                        }
                    });
                });

                // Placeholder AI button
                $('#btnGenerateAi').on('click', function(){
                    if (window.Swal && Swal.fire) {
                        Swal.fire('Info','Función opcional: aquí puedes integrar un generador de títulos/descripciones.','info');
                    } else {
                        alert('Función opcional: aquí puedes integrar un generador de títulos/descripciones.');
                    }
                });

                // Initial upcoming refresh (para mostrar fechas en formato homogéneo)
                refreshUpcoming();
            });
        })();
    </script>
@endsection
