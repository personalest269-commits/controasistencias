<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=UTF-8" />
    <title>Reporte asistencia (Detallado)</title>
    <style>
        table { border-collapse: collapse; width:100%; }
        td, th { border:1px solid #333; padding:4px 6px; font-size:12px; }
        .noborder td { border:0; }
        .muted { color:#666; }
        .center { text-align:center; }
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
            <div class="muted">Reporte detallado de asistencia (por día)</div>
            <div class="muted">Rango: {{ $d1 }} - {{ $d2 }}</div>
        </td>
    </tr>
</table>

<br/>

<table>
    <tr>
        <th>Empresa</th>
        <th>Persona</th>
        <th class="center">Fecha</th>
        <th>Evento</th>
        <th class="center">Estado</th>
        <th>Persona ID</th>
    </tr>
    @foreach(($detalle ?? []) as $r)
        <tr>
            <td>{{ $r['empresa'] }}</td>
            <td>{{ $r['persona'] }}</td>
            <td class="center">{{ $r['fecha'] }}</td>
            <td>{{ $r['evento'] }}</td>
            <td class="center">{{ $r['estado'] }}</td>
            <td>{{ $r['persona_id'] }}</td>
        </tr>
    @endforeach
</table>

</body>
</html>
