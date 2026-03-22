<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia por Día y Evento</title>
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px; vertical-align: top; }
        th { background: #efefef; font-weight: bold; }
        .small { font-size: 11px; }
    </style>
</head>
<body>

<table>
    <tr>
        <th colspan="{{ 2 + count($dates) }}" style="font-size:16px;">Asistencia por Día y Evento</th>
    </tr>
    <tr>
        <td colspan="{{ 2 + count($dates) }}" class="small">Rango: <strong>{{ $desde }}</strong> a <strong>{{ $hasta }}</strong></td>
    </tr>

    <tr>
        <th style="min-width:260px;">Persona</th>
        @foreach($dates as $d)
            <th style="min-width:140px; text-align:center;">{{ $d['label'] }}</th>
        @endforeach
        <th style="min-width:140px; text-align:center;">Totales</th>
    </tr>

    @foreach($rows as $r)
        <tr>
            <td>
                <strong>{{ $r['nombre'] }}</strong><br>
                <span class="small">{{ $r['departamento'] ?: 'Sin departamento' }}</span>
            </td>

            @foreach($dates as $d)
                @php($c = $r['cells'][$d['date']] ?? null)
                <td class="small">
                    @if($c)
                        <div>
                            @if(($c['a'] ?? 0) > 0)
                                <span style="color:green; font-weight:bold;">A {{ $c['a'] }}</span>
                            @endif
                            @if(($c['j'] ?? 0) > 0)
                                <span style="color:#0d6efd; font-weight:bold; margin-left:8px;">J {{ $c['j'] }}</span>
                            @endif
                            @if(($c['f'] ?? 0) > 0)
                                <span style="color:red; font-weight:bold; margin-left:8px;">F {{ $c['f'] }}</span>
                            @endif
                        </div>

                        @foreach(($c['lines'] ?? []) as $ln)
                            @php($s = $ln['s'] ?? 'N')
                            @php($t = $ln['t'] ?? '')
                            <div style="margin-top:2px;">
                                @if($s === 'A')
                                    <span style="color:green; font-weight:bold;">A</span>
                                @elseif($s === 'J')
                                    <span style="color:#0d6efd; font-weight:bold;">J</span>
                                @else
                                    <span style="color:red; font-weight:bold;">F</span>
                                @endif
                                <span style="margin-left:6px;">{{ $t }}</span>
                            </div>
                        @endforeach
                    @endif
                </td>
            @endforeach

            <td class="small" style="text-align:center;">
                <div><strong>Convocados:</strong> {{ $r['totales']['convocados'] ?? 0 }}</div>
                <div>
                    <strong>Asistido</strong> {{ $r['totales']['asistio'] ?? 0 }},
                    <strong>Justificado</strong> {{ $r['totales']['justifico'] ?? 0 }},
                    <strong>Faltas</strong> {{ $r['totales']['no'] ?? 0 }}
                </div>
            </td>
        </tr>
    @endforeach
</table>

</body>
</html>
