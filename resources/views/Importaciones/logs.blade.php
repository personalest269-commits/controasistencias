@extends("templates.".config("sysconfig.theme").".master")

@section("contenido")

<div class="container-fluid">
  <h1>Gestión Log</h1>

  <form method="GET" action="{{ route('importaciones.logs') }}" class="row g-2 mb-3">
    <div class="col-md-4">
      <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar identificación / batch / mensaje">
    </div>
    <div class="col-md-3">
      <input type="text" name="batch" value="{{ $batch }}" class="form-control" placeholder="Batch ID (opcional)">
    </div>
    <div class="col-md-2">
      <select name="accion" class="form-control">
        <option value="">Acción</option>
        <option value="INSERT" {{ $accion=='INSERT'?'selected':'' }}>INSERT</option>
        <option value="UPDATE" {{ $accion=='UPDATE'?'selected':'' }}>UPDATE</option>
        <option value="ERROR" {{ $accion=='ERROR'?'selected':'' }}>ERROR</option>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-primary">Filtrar</button>
      <a href="{{ route('importaciones.logs') }}" class="btn btn-secondary">Limpiar</a>
      <a href="{{ route('importaciones.index') }}" class="btn btn-outline-dark">Historial</a>
    </div>
  </form>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Batch</th>
            <th>Identificación</th>
            <th>Acción</th>
            <th>Mensaje</th>
          </tr>
        </thead>
        <tbody>
          @foreach($logs as $l)
            <tr>
              <td>{{ $l->created_at }}</td>
              <td><a href="{{ route('importaciones.show', $l->batch_id) }}">{{ $l->batch_id }}</a></td>
              <td>{{ $l->identificacion }}</td>
              <td><span class="badge bg-secondary">{{ $l->accion }}</span></td>
              <td>{{ $l->mensaje_error }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      {{ $logs->links() }}
    </div>
  </div>
</div>

@endsection
