<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table{ border-collapse:collapse; width:100%; }
        th, td{ border:1px solid #333; padding:4px 6px; font-size:11px; }
        th{ background:#dbeafe; font-weight:bold; }
        .muted{ color:#666; font-size:10px; }
        .w-persona{ width:260px; }
        .w-day{ width:26px; text-align:center; }
        .w-tot{ width:180px; }
    </style>
</head>
<body>

@php
    $daysHdr = ['L','M','M','J','V','S','D'];
@endphp

@foreach($months as $m)
    <h3 style="margin:8px 0; text-transform:capitalize;">{{ $m['titulo'] }}</h3>
    <div class="muted">L = Lunes, M = Martes/Miércoles, J = Jueves, V = Viernes, S = Sábado, D = Domingo</div>

    <table>
        <thead>
            <tr>
                <th class="w-persona">Persona</th>
                @foreach($m['weeks'] as $wi => $week)
                    @foreach($week as $dateStr)
                        @php
                            $label = '';
                            if ($dateStr) {
                                $d = \Carbon\Carbon::parse($dateStr);
                                $map = ['D','L','M','M','J','V','S'];
                                $abbr = $map[$d->dayOfWeek] ?? '';
                                $label = $abbr.' '.(int)$d->format('d');
                            }
                        @endphp
                        <th class="w-day">{{ $label }}</th>
                    @endforeach
                @endforeach
                <th class="w-tot">Totales</th>
            </tr>
        </thead>
        <tbody>
            @foreach($m['rows'] as $r)
                <tr>
                    <td class="w-persona">
                        <div><strong>{{ $r['nombre'] }}</strong></div>
                        <div class="muted">{{ $r['departamento'] }}</div>
                    </td>
                    @foreach($m['weeks'] as $week)
                        @foreach($week as $dateStr)
                            @php
                                $c = $dateStr ? ($r['cells'][$dateStr] ?? null) : null;
                                $mark = $c['mark'] ?? '';
                            @endphp
                            <td class="w-day">{{ $mark }}</td>
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

    <br/>
@endforeach

</body>
</html>
