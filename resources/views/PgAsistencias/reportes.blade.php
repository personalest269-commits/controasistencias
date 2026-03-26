@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    @parent
    {{-- Flatpickr (ya se usa en Asistencias) + Select2 ("bambox" buscable) --}}
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
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
            <h4 class="mb-0">Reportes de asistencia</h4>
            <small class="text-muted">Asistió = asistencia registrada. Justificó = justificación aprobada (y sin asistencia registrada para el mismo evento/día).</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('PgAsistenciasReportes') }}" class="mb-3">
        <div class="row">
            <div class="col-md-2">
                <label>Desde</label>
                <input type="text" id="desde" name="desde" value="{{ $desde }}" class="form-control" autocomplete="off" />
            </div>
            <div class="col-md-2">
                <label>Hasta</label>
                <input type="text" id="hasta" name="hasta" value="{{ $hasta }}" class="form-control" autocomplete="off" />
            </div>
            <div class="col-md-3">
                <label>Empresa - Departamento (opcional)</label>
                <select id="departamento_id" name="departamento_id" class="form-control">
                    <option value="" {{ !$departamentoId ? 'selected' : '' }}>-- Todos --</option>
                    @foreach($departamentos as $d)
                        @php
                            $empresaNombre = trim((string) optional($d->empresa)->nombre);
                            $depNombre = trim((string) $d->descripcion);
                            $combo = $empresaNombre !== '' ? ($empresaNombre.' - '.$depNombre) : $depNombre;
                        @endphp
                        <option value="{{ $d->id }}" {{ ($departamentoId==$d->id)?'selected':'' }}>{{ $combo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Persona (cédula / apellidos y nombres)</label>
                <select id="persona_id" name="persona_id" class="form-control">
                    <option value="" {{ !$personaId ? 'selected' : '' }}>-- Todas --</option>
                    @foreach(($personasSelect ?? []) as $p)
                        @php
                            $nombre = trim(implode(' ', array_filter([
                                trim((string) $p->apellido1),
                                trim((string) $p->apellido2),
                                trim((string) $p->nombres),
                            ])));
                            $identificacion = trim((string) $p->identificacion);
                            $label = $identificacion !== '' ? ($identificacion.' - '.$nombre) : $nombre;
                        @endphp
                        <option value="{{ $p->id }}" {{ ($personaId==$p->id)?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="mb-3">
        <a class="btn btn-outline-primary btn-sm" href="{{ route('PgAsistenciasReporteDiaEvento', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">Asistencia por Día y Evento</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('PgAsistenciasReporteMes', ['anio'=>\Carbon\Carbon::parse($hasta)->year, 'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">Asistencia por Mes</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReporteExportXlsResumen', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">Exportar XLS (Resumen)</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReporteExportXlsDetalle', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">Exportar XLS (Detallado)</a>
        {{-- PDF en misma pestaña para evitar bloqueos de descarga/pop-up en algunos navegadores --}}
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReporteExportPdfResumen', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">PDF (Resumen)</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReporteExportPdfDetalle', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">PDF (Detallado)</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReporteExport', ['desde'=>$desde,'hasta'=>$hasta,'departamento_id'=>$departamentoId,'persona_id'=>$personaId]) }}">CSV</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasIndex') }}">Volver a asistencia</a>
    </div>

    @php
        $tot = ['convocados'=>0,'asistidos'=>0,'justificados'=>0,'no_asistio'=>0];
        foreach(($resumenDept ?? []) as $g){
            $tot['convocados'] += (int)($g['totales']['convocados'] ?? 0);
            $tot['asistidos'] += (int)($g['totales']['asistidos'] ?? 0);
            $tot['justificados'] += (int)($g['totales']['justificados'] ?? 0);
            $tot['no_asistio'] += (int)($g['totales']['no_asistio'] ?? 0);
        }
    @endphp

    <div class="alert alert-light" style="border-radius:12px; border:1px solid #eee;">
        <div class="d-flex flex-wrap" style="gap:14px;">
            <div><strong>Total convocados:</strong> {{ $tot['convocados'] }}</div>
            <div><strong>Total asistidos:</strong> {{ $tot['asistidos'] }}</div>
            <div><strong>Total justificados:</strong> {{ $tot['justificados'] }}</div>
            <div><strong>Total no asistió:</strong> {{ $tot['no_asistio'] }}</div>
        </div>
    </div>

    @forelse($resumenDept as $g)
        <div class="card mb-3" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
            <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
                <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
                    <strong>{{ $g['departamento'] }}</strong>
                    <div class="text-muted" style="font-size:12px;">
                        Convocados: <strong>{{ $g['totales']['convocados'] }}</strong> | Asistidos: <strong>{{ $g['totales']['asistidos'] }}</strong> | Justificados: <strong>{{ $g['totales']['justificados'] }}</strong> | No asistió: <strong>{{ $g['totales']['no_asistio'] }}</strong>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Persona</th>
                                <th class="text-center">Convocados</th>
                                <th class="text-center">Asistidos</th>
                                <th class="text-center">Justificados</th>
                                <th class="text-center">No asistió</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($g['personas'] as $r)
                                <tr>
                                    <td>{{ $r['nombre'] }}</td>
                                    <td class="text-center">{{ $r['convocados'] }}</td>
                                    <td class="text-center">{{ $r['asistidos'] }}</td>
                                    <td class="text-center">{{ $r['justificados'] }}</td>
                                    <td class="text-center">{{ $r['no_asistio'] }}</td>
                                    <td class="text-right">
                                        <a class="btn btn-info btn-sm" href="{{ route('PgAsistenciasReportePersona', ['personaId'=>$r['persona_id'], 'desde'=>$desde, 'hasta'=>$hasta]) }}">Detalle</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info" style="border-radius:12px;">Sin datos para el rango seleccionado.</div>
    @endforelse
</div>
@endsection

@section('footer')
    @parent
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('vendor/flatpickr/l10n/es.js') }}"></script>
    <script src="{{ asset('admin_lte/plugins/select2/js/select2.min.js') }}"></script>
    <script>
        (function () {
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

            // "Bambox" (Select2): escribible/buscable
            if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
                jQuery('#departamento_id').select2({
                    placeholder: '-- Todos --',
                    allowClear: true,
                    width: '100%'
                });
                jQuery('#persona_id').select2({
                    placeholder: '-- Todas --',
                    allowClear: true,
                    width: '100%'
                });
            }
        })();
    </script>
@endsection
