@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    {{-- Select2 ya viene incluido en el proyecto (AdminLTE). Usamos assets locales para evitar bloqueos de CDN. --}}
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Justificaciones de asistencia</h4>
            <small class="text-muted">Registra una justificación por evento y luego apruébala o recházala.</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
            <div class="d-flex align-items-center justify-content-between" style="gap:12px; flex-wrap:wrap;">
                <strong>Listado</strong>
                <div class="d-flex" style="gap:8px; align-items:center; flex-wrap:wrap;">
                    <form class="form-inline" method="GET" action="{{ route('PgJustificacionesIndex') }}" style="gap:8px; flex-wrap:wrap;">
                        <input type="text" class="form-control" name="persona_q" placeholder="Buscar persona (cédula / nombres)" value="{{ request('persona_q') }}" style="min-width:260px;">
                        <input type="text" class="form-control" id="fecha_filtro" name="fecha" value="{{ request('fecha') }}" placeholder="dd/mm/aaaa" autocomplete="off" style="max-width:140px;">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                        <a class="btn btn-outline-secondary" href="{{ route('PgJustificacionesIndex') }}">Limpiar</a>
                    </form>
                    <button class="btn btn-success" type="button" id="btnNuevo">
                        <i class="fa fa-plus"></i> Nuevo
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Persona</th>
                            <th>Evento</th>
                            <th>Estado</th>
                            <th>Motivo</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td>{{ $r->fecha }}</td>
                                <td>
                                    {{ $r->persona->nombre_completo ?? $r->persona_id }}
                                    @if(!empty($r->persona->identificacion))
                                        <div class="text-muted" style="font-size:12px;">{{ $r->persona->identificacion }}</div>
                                    @endif
                                </td>
                                <td>{{ $r->evento->titulo ?? $r->evento_id }}</td>
                                <td>
                                    @if($r->estado_revision === 'A')
                                        <span class="badge badge-success">APROBADA</span>
                                    @elseif($r->estado_revision === 'R')
                                        <span class="badge badge-danger">RECHAZADA</span>
                                    @else
                                        <span class="badge badge-warning">PENDIENTE</span>
                                    @endif
                                </td>
                                <td style="max-width:420px;">{{ $r->motivo }}</td>
                                <td class="text-right" style="white-space:nowrap;">
                                    <div class="btn-group" role="group" aria-label="Acciones">
                                        <button
                                            class="btn btn-sm btn-primary js-edit"
                                            type="button"
                                            data-id="{{ $r->id }}"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        @if(auth()->user() && auth()->user()->can('pg_justificaciones_aprobaciones'))
                                            @if($r->estado_revision !== 'A')
                                                <form method="POST" action="{{ route('PgJustificacionesAprobar', ['id'=>$r->id]) }}" style="display:inline-block;">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success" type="submit">Aprobar</button>
                                                </form>
                                            @endif
                                            @if($r->estado_revision !== 'R')
                                                <form method="POST" action="{{ route('PgJustificacionesRechazar', ['id'=>$r->id]) }}" style="display:inline-block;">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Rechazar</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Sin registros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Crear/Editar -->
<div class="modal fade" id="justModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header">
                <h5 class="modal-title" id="justModalTitle">Nueva justificación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" id="justForm" action="{{ route('PgJustificacionesStore') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="justId" value="">

                    <div class="row">
                        <div class="col-md-6">
                            <label>Persona<span class="text-danger">*</span></label>
                            <select class="form-control" name="persona_id" id="persona_id" required>
                                <option value="">-- Seleccione --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Evento<span class="text-danger">*</span></label>
                            <select class="form-control" name="evento_id" id="evento_id" required>
                                <option value="">-- Seleccione --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-4">
                            <label>Fecha<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fecha" id="fecha" placeholder="dd/mm/aaaa" autocomplete="off" required />
                        </div>
                        <div class="col-md-8"></div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Motivo<span class="text-danger">*</span></label>
                            <textarea class="form-control" name="motivo" id="motivo" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label>Archivo principal (opcional)</label>
                            <input type="file" class="form-control" name="archivo" accept="image/*,.pdf,.doc,.docx" />
                        </div>
                        <div class="col-md-6">
                            <label>Archivos adicionales (opcional)</label>
                            <input type="file" class="form-control" name="archivos[]" multiple accept="image/*,.pdf,.doc,.docx" />
                        </div>
                    </div>

                    <div class="alert alert-danger d-none mt-3" id="justErr"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnJustSave">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer')
    {{-- Flatpickr (dd/mm/aaaa) --}}
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('vendor/flatpickr/l10n/es.js') }}"></script>

    <script src="{{ asset('admin_lte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        (function(){
            const ROUTES = {
                get: (id) => '{{ url('/admin/PgJustificaciones/obtener') }}/' + id,
                update: (id) => '{{ url('/admin/PgJustificaciones/actualizar') }}/' + id,
                store: '{{ route('PgJustificacionesStore') }}',
                optPersonas: '{{ route('PgJustificacionesOptionsPersonas') }}',
                optEventos: '{{ route('PgJustificacionesOptionsEventos') }}',
                validar: '{{ route('PgJustificacionesValidar') }}'
            };

            const CSRF = '{{ csrf_token() }}';

            let fpFecha = null;
            let fpFechaFiltro = null;
            let suppressFechaChange = false;
            let fechaLocked = false;

            function lockFecha(){
                fechaLocked = true;
                try {
                    if (fpFecha) {
                        // OJO: NO usar disabled en el input con name="fecha" porque NO se envía en el POST.
                        fpFecha._input.removeAttribute('disabled');
                        fpFecha._input.disabled = false;
                        fpFecha._input.setAttribute('readonly', 'readonly');
                        if (fpFecha.altInput) {
                            fpFecha.altInput.setAttribute('readonly', 'readonly');
                            // No hace falta deshabilitar; solo bloqueamos edición.
                            fpFecha.altInput.disabled = false;
                        }
                        // Evita abrir el calendario
                        fpFecha.set('clickOpens', false);
                        fpFecha.set('allowInput', false);
                    } else {
                        // readonly SI se envía; disabled NO.
                        $('#fecha').prop('readonly', true).prop('disabled', false);
                    }
                } catch(e) {}
            }

            function unlockFecha(){
                fechaLocked = false;
                try {
                    if (fpFecha) {
                        fpFecha._input.removeAttribute('readonly');
                        fpFecha._input.removeAttribute('disabled');
                        fpFecha._input.disabled = false;
                        if (fpFecha.altInput) {
                            fpFecha.altInput.removeAttribute('readonly');
                            fpFecha.altInput.disabled = false;
                        }
                        fpFecha.set('clickOpens', true);
                        fpFecha.set('allowInput', true);
                    } else {
                        $('#fecha').prop('readonly', false).prop('disabled', false);
                    }
                } catch(e) {}
            }

            function initFechaPicker(){
                if (fpFecha) return;
                if (!window.flatpickr) return;

                fpFecha = window.flatpickr('#fecha', {
                    locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                    // Valor que se envía al backend
                    dateFormat: 'Y-m-d',
                    // Valor visible en pantalla
                    altInput: true,
                    altFormat: 'd/m/Y',
                    // Permitir seleccionar la fecha
                    allowInput: true,
                    clickOpens: true,
                    disableMobile: true,
                    // En modales, si no se hace appendTo, el calendario a veces no se ve
                    appendTo: document.querySelector('#justModal') || document.body,
                    onChange: function(){
                        // IMPORTANTE: cuando la fecha se setea automáticamente desde el evento,
                        // NO debemos limpiar el evento (si no, "no queda cargada").
                        if (suppressFechaChange) return;

                        // Si el usuario cambia la fecha manualmente (solo cuando está desbloqueada),
                        // limpiamos evento para forzar re-búsqueda con la fecha seleccionada.
                        if (!fechaLocked) {
                            $('#evento_id').val('').trigger('change');
                        }
                        validateLive();
                    }
                });

                // Asegurar que el input real (con name="fecha") quede habilitado para enviarse en el submit.
                try {
                    fpFecha._input.removeAttribute('disabled');
                    fpFecha._input.disabled = false;
                } catch(e) {}

                // Por defecto: la fecha queda bloqueada, se autocompleta al elegir el evento.
                lockFecha();
            }

            function initFechaFiltro(){
                if (fpFechaFiltro) return;
                if (!window.flatpickr) return;
                fpFechaFiltro = window.flatpickr('#fecha_filtro', {
                    locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true,
                    disableMobile: true,
                });
            }

            function setSaveEnabled(enabled){
                $('#btnJustSave').prop('disabled', !enabled);
            }

            function showErr(msg){
                if(!msg){
                    $('#justErr').addClass('d-none').text('');
                    return;
                }
                $('#justErr').removeClass('d-none').text(msg);
            }

            function openNew(){
                $('#justModalTitle').text('Nueva justificación');
                $('#btnJustSave').text('Guardar');
                $('#justForm').attr('action', ROUTES.store);
                $('#justId').val('');
                $('#persona_id').val('').trigger('change');
                $('#evento_id').val('').trigger('change');
                if(fpFecha){ fpFecha.clear(); } else { $('#fecha').val(''); }
                // Inicializar flatpickr antes de bloquear para evitar que #fecha quede disabled sin querer.
                initFechaPicker();
                lockFecha();
                $('#motivo').val('');
                showErr('');
                setSaveEnabled(false);
                $('#justModal').modal('show');
            }

            function openEdit(id){
                $('#justErr').addClass('d-none').text('');
                $.getJSON(ROUTES.get(id))
                    .done(function(res){
                        $('#justModalTitle').text('Editar justificación');
                        $('#btnJustSave').text('Actualizar');
                        $('#justForm').attr('action', ROUTES.update(id));
                        $('#justId').val(id);
                        // set persona y evento (Select2 AJAX) con option temporal
                        if(res.persona_id){
                            const optP = new Option(res.persona_text || res.persona_id, res.persona_id, true, true);
                            $('#persona_id').append(optP).trigger('change');
                        } else {
                            $('#persona_id').val('').trigger('change');
                        }
                        if(res.evento_id){
                            const optE = new Option(res.evento_text || res.evento_id, res.evento_id, true, true);
                            $('#evento_id').append(optE).trigger('change');
                        } else {
                            $('#evento_id').val('').trigger('change');
                        }
                        if(fpFecha && res.fecha){
                            fpFecha.setDate(res.fecha, true, 'Y-m-d');
                        } else {
                            $('#fecha').val(res.fecha || '');
                        }
                        lockFecha();
                        $('#motivo').val(res.motivo || '');
                        setSaveEnabled(true);
                        $('#justModal').modal('show');
                    })
                    .fail(function(){
                        showErr('No se pudo cargar la justificación.');
                        $('#justModal').modal('show');
                    });
            }

            function validateLive(){
                const personaId = ($('#persona_id').val() || '').toString().trim();
                const eventoId = ($('#evento_id').val() || '').toString().trim();
                const fecha = ($('#fecha').val() || '').toString().trim();
                const ignoreId = ($('#justId').val() || '').toString().trim();

                if(!personaId || !eventoId || !fecha){
                    showErr('');
                    setSaveEnabled(false);
                    return;
                }

                $.ajax({
                    method: 'POST',
                    url: ROUTES.validar,
                    data: { _token: CSRF, persona_id: personaId, evento_id: eventoId, fecha: fecha, ignore_id: ignoreId }
                }).done(function(res){
                    if(res && res.ok){
                        showErr('');
                        setSaveEnabled(true);
                    } else {
                        showErr((res && res.message) ? res.message : 'Validación fallida.');
                        setSaveEnabled(false);
                    }
                }).fail(function(){
                    showErr('No se pudo validar la selección.');
                    setSaveEnabled(false);
                });
            }

            function initSelect2InModal(){
                // Evitar doble inicialización
                if ($.fn.select2 && $('#persona_id').data('select2')) return;

                const common = {
                    width: '100%',
                    theme: 'bootstrap4',
                    dropdownParent: $('#justModal'),
                    // Mensajes en español (evita "The results could not be loaded")
                    language: {
                        errorLoading: function(){ return 'No se pudieron cargar los resultados.'; },
                        inputTooShort: function(){ return 'Escribe para buscar...'; },
                        loadingMore: function(){ return 'Cargando más resultados...'; },
                        noResults: function(){ return 'No hay resultados.'; },
                        searching: function(){ return 'Buscando...'; },
                        removeAllItems: function(){ return 'Quitar todos'; }
                    }
                };

                // Personas
                $('#persona_id').select2(Object.assign({}, common, {
                    placeholder: '-- Seleccione --',
                    ajax: {
                        url: ROUTES.optPersonas,
                        dataType: 'json',
                        delay: 250,
                        data: function(params){
                            return {
                                q: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data){
                            return data;
                        }
                    }
                }));

                // Eventos: filtrados por persona + (opcional) fecha
                $('#evento_id').select2(Object.assign({}, common, {
                    placeholder: '-- Seleccione --',
                    ajax: {
                        url: ROUTES.optEventos,
                        dataType: 'json',
                        delay: 250,
                        data: function(params){
                            return {
                                q: params.term || '',
                                page: params.page || 1,
                                persona_id: ($('#persona_id').val() || ''),
                                // Filtrar por la fecha seleccionada (si existe)
                                fecha: ($('#fecha').val() || '')
                            };
                        },
                        processResults: function(data){
                            return data;
                        }
                    }
                }));

                // Validar al seleccionar evento
                $('#evento_id').on('select2:select', function(e){
                    // Al seleccionar un evento: auto-set de fecha de falta y bloquear la fecha.
                    const data = e.params && e.params.data ? e.params.data : null;
                    const faltaFecha = data && data.falta_fecha ? String(data.falta_fecha).trim() : '';
                    // Fallback: usar fecha_inicio (solo fecha)
                    const inicio = data && data.inicio ? String(data.inicio).trim() : '';
                    let newFecha = faltaFecha;
                    if (!newFecha && inicio && inicio.length >= 10) {
                        newFecha = inicio.substring(0,10);
                    }

                    if (newFecha) {
                        suppressFechaChange = true;
                        try {
                            if (fpFecha) {
                                fpFecha.setDate(newFecha, true, 'Y-m-d');
                            } else {
                                $('#fecha').val(newFecha);
                            }
                        } finally {
                            // microtask para evitar que el onChange limpie evento
                            setTimeout(() => { suppressFechaChange = false; }, 0);
                        }
                        lockFecha();
                    }

                    validateLive();
                });

                // Ajuste de z-index dentro de modal (por si el dropdown se esconde)
                $(document).on('select2:open', () => {
                    $('.select2-container--open').css('z-index', 99999);
                });
            }

            $(function(){
                initFechaFiltro();

                // Inicializar select2 al abrir el modal (asegura que aparezca el input de búsqueda)
                $('#justModal').on('shown.bs.modal', function(){
                    initSelect2InModal();
                    initFechaPicker();
                });

                // Si el modal ya está visible por algún motivo, inicializa
                if ($('#justModal').is(':visible')) {
                    initSelect2InModal();
                }

                $('#btnNuevo').on('click', openNew);
                $(document).on('click', '.js-edit', function(){
                    openEdit($(this).data('id'));
                });

                // Si cambia la persona, limpiar evento (porque depende de persona + fecha)
                $('#persona_id').on('change', function(){
                    $('#evento_id').val('').trigger('change');
                    // Si había una fecha cargada, la borramos para que se vuelva a setear desde el nuevo evento
                    if (fpFecha) { fpFecha.clear(); }
                    $('#fecha').val('');
                    unlockFecha();
                    validateLive();
                });

                // Fecha bloqueada: no recargamos eventos por cambio de fecha.
                // (La fecha se setea automáticamente al seleccionar el evento.)

                // Validar principalmente cuando cambie Evento (persona/fecha ya lo gestionan)
                $('#evento_id').on('change', validateLive);

                // Bloqueo por si intentan guardar sin validar
                $('#justForm').on('submit', function(e){
                    if($('#btnJustSave').prop('disabled')){
                        e.preventDefault();
                        showErr('Complete Persona, Evento y Fecha válidos antes de guardar.');
                        return false;
                    }
                });
            });
        })();
    </script>
@endsection
