@extends("templates.".config("sysconfig.theme").".master")

@section("contenido")

<div class="container-fluid">
  <h1>Detalle de Importación</h1>
  <div class="mb-3">
    <a href="{{ route('importaciones.index') }}" class="btn btn-secondary">Volver</a>
    <a href="{{ route('importaciones.logs', ['batch' => $batch->batch_id]) }}" class="btn btn-outline-dark">Ver logs</a>
    @if($batch->estado === 'APLICADO')
      <form method="POST" action="{{ route('personas.import.rollback', $batch->batch_id) }}" style="display:inline">
        @csrf
        <button class="btn btn-danger" onclick="return confirm('¿Seguro de hacer rollback del batch?')">Rollback</button>
      </form>
    @endif
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div><b>Batch:</b> {{ $batch->batch_id }}</div>
      <div><b>Fuente:</b> {{ $batch->fuente }}</div>
      <div><b>Estado:</b> {{ $batch->estado }}</div>
      <div><b>Insert:</b> {{ $batch->total_insert }} | <b>Update:</b> {{ $batch->total_update }} | <b>Bajas:</b> {{ $batch->total_bajas ?? 0 }} | <b>Errores:</b> {{ $batch->total_errores }}</div>
      <div><b>Aplicado:</b> {{ $batch->aplicado_at }} | <b>Rollback:</b> {{ $batch->rollback_at }}</div>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Identificación</th>
            <th>Acción</th>
            <th>Mensaje</th>
            <th>Before</th>
            <th>After</th>
          </tr>
        </thead>
        <tbody>
          @foreach($logs as $l)
            <tr>
              <td>{{ $l->created_at }}</td>
              <td>{{ $l->identificacion }}</td>
              <td><span class="badge bg-secondary">{{ $l->accion }}</span></td>
              <td>{{ $l->mensaje_error }}</td>
              <td>
                @if($l->before_json)
                  <details><summary>Ver</summary><pre style="max-width:600px; white-space:pre-wrap">{{ $l->before_json }}</pre></details>
                @endif
              </td>
              <td>
                @if($l->after_json)
                  <details><summary>Ver</summary><pre style="max-width:600px; white-space:pre-wrap">{{ $l->after_json }}</pre></details>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      {{ $logs->links() }}
    </div>
  </div>
</div>

@endsection
