@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Reporte detallado por persona</h4>
            <small class="text-muted">{{ $persona->nombre_completo }} ({{ $persona->id }})</small>
        </div>
    </div>

    <div class="mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('PgAsistenciasEmpresaReportes', ['desde'=>$desde,'hasta'=>$hasta, 'empresa_id'=>($empresaId ?? null)]) }}">Volver</a>
    </div>

    <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
            @php
                $d1 = \App\Models\PgConfiguracion::formatFechaSolo($desde);
                $d2 = \App\Models\PgConfiguracion::formatFechaSolo($hasta);
            @endphp
            <strong>Detalle ({{ $d1 }} a {{ $d2 }})</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Evento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($detail as $r)
                            <tr>
                                <td>{{ $r['fecha'] }}</td>
                                <td>{{ $r['evento'] }}</td>
                                <td>
                                    @if($r['estado'] === 'ASISTIÓ')
                                        <span class="badge badge-success">ASISTIÓ</span>
                                    @elseif($r['estado'] === 'JUSTIFICÓ')
                                        <span class="badge badge-warning">JUSTIFICÓ</span>
                                    @else
                                        <span class="badge badge-secondary">NO ASISTIÓ</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">Sin detalle.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
