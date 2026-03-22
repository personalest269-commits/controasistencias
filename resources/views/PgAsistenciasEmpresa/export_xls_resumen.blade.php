<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=UTF-8" />
    <title>Reporte asistencia (Resumen)</title>
    <style>
        table { border-collapse: collapse; }
        td, th { border:1px solid #333; padding:4px 6px; font-size:12px; }
        .noborder td { border:0; }
        .muted { color:#666; }
        .center { text-align:center; }
        .right { text-align:right; }
        .dept { background:#f3f3f3; font-weight:bold; }
    </style>
</head>
<body>
@php
    $d1 = \App\Models\PgConfiguracion::formatFechaSolo($desde);
    $d2 = \App\Models\PgConfiguracion::formatFechaSolo($hasta);
@endphp

<table class="noborder">
    <tr>
        <td style="width:110px;">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" style="max-height:60px;" alt="logo" />
            @endif
        </td>
        <td>
            <div style="font-size:16px; font-weight:bold;">{{ $nombreSistema }}</div>
            <div class="muted">Reporte general de asistencia por empresa</div>
            <div class="muted">Rango: {{ $d1 }} - {{ $d2 }}</div>
        </td>
    </tr>
</table>

<br/>

@foreach(($resumenDept ?? []) as $g)
    <table style="width:100%;">
        <tr class="dept">
            <td colspan="6">
                {{ $g['empresa'] }}
                <span class="muted" style="font-weight:normal;">
                    (Convocados: {{ $g['totales']['convocados'] }}, Asistidos: {{ $g['totales']['asistidos'] }}, Justificados: {{ $g['totales']['justificados'] }}, No asistió: {{ $g['totales']['no_asistio'] }})
                </span>
            </td>
        </tr>
        <tr>
            <th>Persona</th>
            <th class="center">Convocados</th>
            <th class="center">Asistidos</th>
            <th class="center">Justificados</th>
            <th class="center">No asistió</th>
            <th>Persona ID</th>
        </tr>
        @foreach(($g['personas'] ?? []) as $r)
            <tr>
                <td>{{ $r['nombre'] }}</td>
                <td class="center">{{ $r['convocados'] }}</td>
                <td class="center">{{ $r['asistidos'] }}</td>
                <td class="center">{{ $r['justificados'] }}</td>
                <td class="center">{{ $r['no_asistio'] }}</td>
                <td>{{ $r['persona_id'] }}</td>
            </tr>
        @endforeach
    </table>
    <br/>
@endforeach

</body>
</html>
