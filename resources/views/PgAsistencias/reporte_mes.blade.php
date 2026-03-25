@extends("templates.".config("sysconfig.theme").".master")

@section('head')
    @parent
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/plugins/monthSelect/style.css') }}">
    <link rel="stylesheet" href="{{ asset('admin_lte/plugins/select2/css/select2.min.css') }}">
    <style>
        .select2-container{ width:100% !important; }
        .select2-container .select2-selection--single{ height:38px; padding:4px 8px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered{ line-height:28px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow{ height:38px; }

        .tbl-mes{ width:100%; border-collapse:collapse; }
        .tbl-mes th, .tbl-mes td{ border:1px solid #e5e7eb; padding:4px 6px; font-size:11px; }
        .tbl-mes thead th{ background:#f8fafc; font-weight:700; }
        .w-persona{ width:260px; }
        .w-day{ width:32px; text-align:center; }
        .w-tot{ width:160px; }
        /* Encabezado sombreado: texto negro (como pediste) */
        .hdr-blue{ background:#dbeafe; color:#000; font-weight:700; }
        .mark-a, .mark-j, .mark-n, .mark-mix{ font-weight:800; display:inline-block; margin-right:2px; }
        .mark-a{ color:#16a34a; }
        .mark-j{ color:#2563eb; }
        .mark-n{ color:#dc2626; }
        .mark-mix{ color:#7c3aed; }
        .event-code{ font-size:9px; color:#475569; line-height:1.2; text-align:left; margin-top:2px; }
        .event-code .event-item{ white-space:normal; margin-bottom:2px; }
        .event-item .event-status{ font-weight:800; margin-right:3px; }
        .event-item.event-a .event-status{ color:#16a34a; }
        .event-item.event-j .event-status{ color:#2563eb; }
        .event-item.event-f .event-status{ color:#dc2626; }
        .event-item.event-x .event-status{ color:#64748b; }

        .day-vertical{ height:72px; vertical-align:bottom; padding:0 2px !important; }
        .day-vertical .day-name{
            display:inline-block; writing-mode:vertical-rl; transform:rotate(180deg);
            font-weight:700; letter-spacing:.3px; font-size:10px; line-height:1;
        }

        @media print{
            .no-print{ display:none !important; }
            body{ background:#fff !important; }
            .card{ box-shadow:none !important; border:0 !important; }
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3 no-print">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
            <div>
                <h4 class="mb-0">Asistencia por Mes</h4>
                <small class="text-muted">Calendario por semanas (L-D). Si el año seleccionado es el actual, solo se muestran meses hasta el mes actual.</small>
            </div>
            <div class="d-flex" style="gap:8px;">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasReportes') }}">Volver a Reportes</a>
                <a class="btn btn-outline-success btn-sm"
                   href="{{ route('PgAsistenciasReporteMesXls', request()->all()) }}">Exportar Excel</a>
                <a class="btn btn-outline-primary btn-sm"
                   href="{{ route('PgAsistenciasReporteMesPdf', request()->all()) }}">Exportar PDF</a>
            </div>
        </div>
    </div>

    <div class="card mb-3 no-print" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-body">
            <form method="GET" action="{{ route('PgAsistenciasReporteMes') }}">
                <div class="row">
                    <div class="col-md-2">
                        <label>Año</label>
                        @php
                            $currentYear = (int)\Carbon\Carbon::today()->year;
                            // Solo hasta el año actual (no debe aparecer 2027, etc.)
                            $years = range($currentYear - 5, $currentYear);
                        @endphp
                        <select name="anio" class="form-control">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ ((int)$anio === (int)$y)?'selected':'' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Mes</label>
                        <input type="text" id="mes" name="mes" value="{{ $mes ? sprintf('%04d-%02d', $anio, $mes) : '' }}" class="form-control" autocomplete="off" placeholder="Selecciona un mes" />
                        <small class="text-muted">Formato: AAAA-MM</small>
                    </div>

                    <div class="col-md-2">
                        <label>Departamento</label>
                        <select id="departamento_id" name="departamento_id" class="form-control">
                            <option value="" {{ !$departamentoId ? 'selected' : '' }}>-- Todos --</option>
                            @foreach($departamentos as $d)
                                <option value="{{ $d->id }}" {{ ($departamentoId==$d->id)?'selected':'' }}>{{ $d->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Persona (opcional)</label>
                        <select id="persona_id" name="persona_id" class="form-control">
                            <option value="" {{ !$personaId ? 'selected' : '' }}>-- Todas --</option>
                            @foreach($personasSelect as $p)
                                <option value="{{ $p->id }}" {{ ($personaId==$p->id)?'selected':'' }}>{{ $p->nombre_completo }}{{ optional($p->departamento)->descripcion ? ' - '.optional($p->departamento)->descripcion : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-flex align-items-start w-100" style="gap:16px; border-left:1px solid #d1d5db; padding-left:14px;">
                            <div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="todos_meses" name="todos_meses" {{ $todosMeses ? 'checked' : '' }}>
                                    <label class="form-check-label" for="todos_meses">Todos los meses</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="solo_eventos" name="solo_eventos" {{ !empty($soloEventos) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="solo_eventos">Solo eventos</label>
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit">Filtrar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @forelse($months as $m)
        <div class="card mb-4" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
            <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
                    <div>
                        <strong style="font-size:16px; text-transform:capitalize;">{{ $m['titulo'] }}</strong>
                                <div class="text-muted" style="font-size:12px;">L = Lunes, M = Martes/Miércoles, J = Jueves, V = Viernes, S = Sábado, D = Domingo</div>
                    </div>
                </div>
            </div>

            <div class="card-body" style="overflow:auto;">
                @if(!($m['has_events'] ?? false))
                    <div class="alert alert-info text-center mb-0" style="font-size:22px; font-weight:700;">
                        NO HUBO EVENTOS
                    </div>
                    @continue
                @endif
                <table class="tbl-mes">
                    <thead>
                        <tr>
                            <th class="w-persona hdr-blue">Persona</th>
                            @foreach($m['weeks'] as $wi => $week)
                                <th class="hdr-blue" colspan="7" style="text-align:center;">Semana {{ $wi + 1 }}</th>
                            @endforeach
                            <th class="w-tot hdr-blue">Totales</th>
                        </tr>
                        <tr>
                            <th class="w-persona">&nbsp;</th>
                            @foreach($m['weeks'] as $week)
                                @foreach(['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'] as $d)
                                    <th class="w-day day-vertical"><span class="day-name">{{ $d }}</span></th>
                                @endforeach
                            @endforeach
                            <th class="w-tot">&nbsp;</th>
                        </tr>
                        <tr>
                            <th class="w-persona">&nbsp;</th>
                            @foreach($m['weeks'] as $week)
                                @foreach($week as $dateStr)
                                    <th class="w-day">{{ $dateStr ? (int)\Carbon\Carbon::parse($dateStr)->format('d') : '' }}</th>
                                @endforeach
                            @endforeach
                            <th class="w-tot">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($m['rows'] as $r)
                            <tr>
                                <td class="w-persona">
                                    <div style="font-weight:700;">{{ $r['nombre'] }}</div>
                                    <div class="text-muted" style="font-size:10px;">{{ $r['departamento'] }}</div>
                                </td>

                                @foreach($m['weeks'] as $week)
                                    @foreach($week as $dateStr)
                                        @php
                                            $c = $dateStr ? ($r['cells'][$dateStr] ?? null) : null;
                                            $mark = $c['mark'] ?? '';
                                            $cls = '';
                                            if($mark==='A') $cls='mark-a';
                                            elseif($mark==='J') $cls='mark-j';
                                            elseif($mark==='F') $cls='mark-n';
                                            elseif(str_contains($mark, 'F')) $cls='mark-n';
                                            elseif(str_contains($mark, '/')) $cls='mark-mix';
                                            $eventCodes = $c['event_codes'] ?? [];
                                            $statusCounts = ['A' => 0, 'F' => 0, 'J' => 0];
                                            foreach($eventCodes as $eventCodeTmp){
                                                $eventCodeNorm = strtoupper(ltrim(strip_tags((string)$eventCodeTmp)));
                                                if (preg_match('/\(([AFJ])\)/i', $eventCodeNorm, $ms)) {
                                                    $statusCounts[strtoupper($ms[1])]++;
                                                } elseif (preg_match('/^([AFJ])(?:\\b|[^A-Z])/', $eventCodeNorm, $ms)) {
                                                    $statusCounts[strtoupper($ms[1])]++;
                                                } elseif (in_array($mark, ['A','F','J'], true)) {
                                                    $statusCounts[$mark]++;
                                                }
                                            }
                                        @endphp
                                        <td class="w-day">
                                            @if($mark)
                                                @if(array_sum($statusCounts) > 0)
                                                    @if($statusCounts['A'] > 0)<span class="mark-a">A({{ $statusCounts['A'] }})</span>@endif
                                                    @if($statusCounts['F'] > 0)<span class="mark-n">F({{ $statusCounts['F'] }})</span>@endif
                                                    @if($statusCounts['J'] > 0)<span class="mark-j">J({{ $statusCounts['J'] }})</span>@endif
                                                @else
                                                    <span class="{{ $cls }}">{{ $mark }}</span>
                                                @endif
                                                @if(!empty($eventCodes))
                                                    <div class="event-code" title="{{ implode(' | ', $eventCodes) }}">
                                                        @foreach($eventCodes as $eventCode)
                                                            @php
                                                                $eventCodeNorm = strtoupper(ltrim(strip_tags((string)$eventCode)));
                                                                $status = '';
                                                                if (preg_match('/\(([AFJ])\)/', $eventCodeNorm, $mch)) {
                                                                    $status = strtoupper($mch[1]);
                                                                } elseif (preg_match('/^([AFJ])(?:\\b|[^A-Z])/', $eventCodeNorm, $mch)) {
                                                                    $status = strtoupper($mch[1]);
                                                                } elseif (in_array($mark, ['A','F','J'], true)) {
                                                                    $status = $mark;
                                                                }
                                                                $eventClass = $status === 'A' ? 'event-a' : ($status === 'J' ? 'event-j' : ($status === 'F' ? 'event-f' : 'event-x'));
                                                                $eventText = preg_replace('/\(([AFJ])\)/i', '', $eventCode);
                                                                $eventText = preg_replace('/^\s*[AFJ][\s\-\:\.]+/i', '', $eventText);
                                                                $eventText = preg_replace('/^\s*[AFJ]\s*/i', '', $eventText);
                                                                $eventText = trim($eventText);
                                                            @endphp
                                                            <div class="event-item {{ $eventClass }}">
                                                                @if($status)<span class="event-status">{{ $status }}</span>@endif{{ $eventText }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @else
                                                &nbsp;
                                            @endif
                                        </td>
                                    @endforeach
                                @endforeach

                                <td class="w-tot">
                                    <div><strong>Convocados:</strong> {{ $r['totales']['convocados'] ?? 0 }}</div>
                                    <div>
                                        <strong>Asistido</strong> {{ $r['totales']['asistio'] ?? 0 }},
                                        <strong>Justificado</strong> {{ $r['totales']['justifico'] ?? 0 }},
                                        <strong>Faltas</strong> {{ $r['totales']['no'] ?? 0 }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-warning">No hay datos para los filtros seleccionados.</div>
    @endforelse
</div>
@stop

@section('footer')
@parent
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/l10n/es.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/plugins/monthSelect/index.js') }}"></script>
<script src="{{ asset('admin_lte/plugins/select2/js/select2.full.min.js') }}"></script>
<script>
(function(){
    function init(){
        // Select2
        if (window.$ && $.fn.select2) {
            $('#departamento_id').select2({ placeholder: 'Departamento', allowClear: true, width: 'resolve' });
            $('#persona_id').select2({ placeholder: 'Persona', allowClear: true, width: 'resolve' });
        }

        // Flatpickr month select
        if (window.flatpickr && window.monthSelectPlugin) {
            const fp = flatpickr('#mes', {
                locale: (flatpickr.l10ns && flatpickr.l10ns.es) ? 'es' : undefined,
                plugins: [
                    new monthSelectPlugin({
                        shorthand: true,
                        dateFormat: 'Y-m',
                        altFormat: 'F Y',
                        theme: 'light'
                    })
                ],
                allowInput: true,
                onChange: function(){
                    // Si el usuario selecciona un mes, desmarcamos "Todos los meses"
                    const chk = document.getElementById('todos_meses');
                    if (chk) chk.checked = false;
                    const mes = document.getElementById('mes');
                    if (mes) mes.disabled = false;

                    // Sincronizar Año con el mes seleccionado (evita anio=2024&mes=2026-02)
                    try {
                        const val = (mes && mes.value) ? String(mes.value) : '';
                        const parts = val.split('-');
                        if (parts.length === 2) {
                            const y = parseInt(parts[0], 10);
                            const anioSel = document.getElementById('anio');
                            if (anioSel && y) anioSel.value = String(y);
                        }
                    } catch(e) {}
                }
            });
        }

        const chk = document.getElementById('todos_meses');
        const mes = document.getElementById('mes');
        function toggle(){
            const on = chk && chk.checked;
            if (mes) {
                mes.disabled = !!on;
                if (on) mes.value = '';
            }
        }
        if (chk) {
            chk.addEventListener('change', toggle);
            toggle();
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endsection
