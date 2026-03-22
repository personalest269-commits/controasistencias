@extends("templates.".config("sysconfig.theme").".master")

@section('title', 'Cierre automático de asistencia')

@section('content_header')
    <h1>Cierre automático de asistencia</h1>
    <p class="text-muted mb-0">Este proceso marca <strong>Faltas (F)</strong> para los eventos aplicables del día cuando no existe <strong>Asistencia (A)</strong> ni <strong>Justificación aprobada</strong>. El sistema está programado para ejecutarse a las <strong>{{ $horaProgramada }}</strong> (servidor).</p>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Ejecutar manualmente</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('PgCierreAsistenciaCronEjecutar') }}">
                        @csrf
                        <div class="form-group">
                            <label for="fecha">Fecha</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" value="{{ $fecha }}" required>
                            <small class="text-muted">Recomendado: ejecutar después de terminar la jornada (ej. 20:00).</small>
                        </div>

                        <button type="submit" class="btn btn-primary" onclick="return confirm('¿Seguro que deseas ejecutar el cierre para esta fecha? Esto marcará faltas para quienes no tengan asistencia ni justificación.')">
                            Ejecutar cierre ahora
                        </button>
                    </form>

                    <hr>
                    <div class="alert alert-info mb-0">
                        <strong>Importante (CRON):</strong>
                        En el servidor debes tener configurado <code>php artisan schedule:run</code> cada minuto.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Logs (últimos 100)</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Estado</th>
                            <th>Personas</th>
                            <th>Eventos</th>
                            <th>Faltas nuevas</th>
                            <th>Faltas act.</th>
                            <th>Mensaje</th>
                            <th>Run by</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $l)
                            <tr>
                                <td>{{ $l->id }}</td>
                                <td>{{ $l->fecha }}</td>
                                <td>{{ $l->started_at }}</td>
                                <td>{{ $l->finished_at }}</td>
                                <td>
                                    @if($l->status === 'OK')
                                        <span class="badge badge-success">OK</span>
                                    @elseif($l->status === 'ERROR')
                                        <span class="badge badge-danger">ERROR</span>
                                    @else
                                        <span class="badge badge-warning">{{ $l->status }}</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ (int)$l->total_personas }}</td>
                                <td class="text-right">{{ (int)$l->total_eventos }}</td>
                                <td class="text-right">{{ (int)$l->faltas_nuevas }}</td>
                                <td class="text-right">{{ (int)$l->faltas_actualizadas }}</td>
                                <td style="min-width:220px">{{ $l->message }}</td>
                                <td>{{ $l->run_by }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">Sin ejecuciones registradas.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
