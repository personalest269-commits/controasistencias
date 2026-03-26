@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    @parent
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Asistencia por Día y Evento (Modo Dual)</h4>
            <small class="text-muted">En cada celda: A completo (verde), AI incompleto (amarillo), J justificado (azul), F falta (rojo).</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('PgAsistenciasReporteDiaEventoDual') }}" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <label>Desde</label>
                <input type="text" id="desde" name="desde" value="{{ $desde }}" class="form-control" autocomplete="off" />
            </div>
            <div class="col-md-3">
                <label>Hasta</label>
                <input type="text" id="hasta" name="hasta" value="{{ $hasta }}" class="form-control" autocomplete="off" />
            </div>
            <div class="col-md-3">
                <label>Departamento (opcional)</label>
                <select name="departamento_id" id="departamento_id" class="form-control">
                    <option value="" {{ !$departamentoId ? 'selected' : '' }}>-- Todos --</option>
                    @foreach($departamentos as $d)
                        <option value="{{ $d->id }}" {{ ($departamentoId==$d->id)?'selected':'' }}>{{ $d->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Persona (opcional)</label>
                <select name="persona_id" id="persona_id" class="form-control">
                    <option value="">-- Todas --</option>
                    @foreach($personasSelect as $p)
                        <option value="{{ $p->id }}" {{ ($personaId==$p->id)?'selected':'' }}>{{ $p->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Filtrar</button>
            </div>
            <div class="col-md-10 d-flex align-items-end" style="gap:8px; flex-wrap:wrap;">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReporteDiaEventoXls', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">Exportar XLS</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReporteDiaEventoPdf', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">Exportar PDF</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReportesDual', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId]) }}">Ir a Reportes Dual</a>
            </div>
        </div>
    </form>

    <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-body">
            @if(empty($dates))
                <div class="text-muted">No existen eventos dentro del rango seleccionado.</div>
            @else
                <div class="table-responsive" style="max-height:70vh;">
                    <table class="table table-sm table-hover table-bordered" style="min-width: 900px;">
                        <thead style="position: sticky; top: 0; z-index: 2; background: #fff;">
                            <tr>
                                <th style="min-width:260px;">Persona</th>
                                @foreach($dates as $d)
                                    <th class="text-center" style="min-width:130px;">{{ $d['label'] }}</th>
                                @endforeach
                                <th class="text-center" style="min-width:140px;">Totales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $r)
                                <tr>
                                    <td>
                                        <div style="font-weight:600;">{{ $r['nombre'] }}</div>
                                        <div class="text-muted" style="font-size:12px;">{{ $r['departamento'] ?: 'Sin departamento' }}</div>
                                    </td>
                                    @foreach($dates as $d)
                                        @php($c = $r['cells'][$d['date']] ?? null)
                                        <td style="vertical-align:top; font-size:12px;">
                                            @if($c)
                                                <div class="mb-1" style="display:flex; gap:6px; flex-wrap:wrap;">
                                                    @if(($c['a'] ?? 0) > 0)
                                                        <span class="badge badge-success">A {{ $c['a'] }}</span>
                                                    @endif
                                                    @if(($c['ai'] ?? 0) > 0)
                                                        <span class="badge badge-warning">AI {{ $c['ai'] }}</span>
                                                    @endif
                                                    @if(($c['j'] ?? 0) > 0)
                                                        <span class="badge badge-primary">J {{ $c['j'] }}</span>
                                                    @endif
                                                    @if(($c['f'] ?? 0) > 0)
                                                        <span class="badge badge-danger">F {{ $c['f'] }}</span>
                                                    @endif
                                                </div>

                                                @foreach(($c['lines'] ?? []) as $ln)
                                                    @php($s = $ln['s'] ?? 'N')
                                                    @php($t = $ln['t'] ?? '')
                                                    <div style="line-height:1.1; margin-bottom:2px;">
                                                        @if($s === 'A')
                                                            <span style="color:#28a745; font-weight:700;">A</span>
                                                        @elseif($s === 'AI')
                                                            <span style="color:#e0a800; font-weight:700;">AI</span>
                                                        @elseif($s === 'J')
                                                            <span style="color:#007bff; font-weight:700;">J</span>
                                                        @else
                                                            <span style="color:#dc3545; font-weight:700;">F</span>
                                                        @endif
                                                        <span style="margin-left:6px;">{{ $t }}</span>
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-muted">&nbsp;</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center" style="font-size:12px;">
                                        <div><strong>Convocados:</strong> {{ $r['totales']['convocados'] ?? 0 }}</div>
                                        <div>
                                            <strong>Asistido</strong> {{ $r['totales']['asistio'] ?? 0 }},
                                            <strong>Incompleto</strong> {{ $r['totales']['incompleto'] ?? 0 }},
                                            <strong>Justificado</strong> {{ $r['totales']['justifico'] ?? 0 }},
                                            <strong>Faltas</strong> {{ $r['totales']['no'] ?? 0 }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@stop

@section('footer')
@parent
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/l10n/es.js') }}"></script>
<script src="{{ asset('admin_lte/plugins/select2/js/select2.full.min.js') }}"></script>
<script>
(function(){
    function init(){
        // Flatpickr: mostrar d/m/Y, enviar Y-m-d
        if (window.flatpickr) {
            const common = {
                locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
            };
            flatpickr('#desde', common);
            flatpickr('#hasta', common);
        }

        if (!window.jQuery || !$.fn.select2) return;

        $('#departamento_id').select2({
            width: '100%',
            theme: 'bootstrap4',
            placeholder: '-- Todos --',
            allowClear: true
        }).on('change', function(){
            // Para refrescar combo de personas según departamento
            // (en el backend, personasSelect ya viene filtrado por departamento)
            // Auto-enviar para que el usuario no tenga que volver a elegir.
            this.form.submit();
        });

        $('#persona_id').select2({
            width: '100%',
            theme: 'bootstrap4',
            placeholder: '-- Todas --',
            allowClear: true
        });
    }

    $(document).ready(init);
})();
</script>
@stop
