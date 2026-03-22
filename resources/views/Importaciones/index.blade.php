@extends("templates.".config("sysconfig.theme").".master")

@section("contenido")

<div class="container-fluid">
  <h1>Gestión de Importaciones</h1>

  <form method="GET" action="{{ route('importaciones.index') }}" class="row g-2 mb-3">
    <div class="col-md-4">
      <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar batch / archivo / url">
    </div>
    <div class="col-md-2">
      <select name="fuente" class="form-control">
        <option value="">Fuente</option>
        <option value="XLS" {{ $fuente=='XLS'?'selected':'' }}>XLS</option>
        <option value="API" {{ $fuente=='API'?'selected':'' }}>API</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="estado" class="form-control">
        <option value="">Estado</option>
        <option value="CARGADO" {{ $estado=='CARGADO'?'selected':'' }}>CARGADO</option>
        <option value="PREVIEW" {{ $estado=='PREVIEW'?'selected':'' }}>PREVIEW</option>
        <option value="APLICADO" {{ $estado=='APLICADO'?'selected':'' }}>APLICADO</option>
        <option value="ROLLBACK" {{ $estado=='ROLLBACK'?'selected':'' }}>ROLLBACK</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary">Filtrar</button>
      <a href="{{ route('importaciones.index') }}" class="btn btn-secondary">Limpiar</a>
    </div>
    <div class="col-md-2 text-end">
      <a href="{{ route('importaciones.logs') }}" class="btn btn-outline-dark">Gestión Log</a>
    </div>
  </form>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>Batch</th>
            <th>Fuente</th>
            <th>Estado</th>
            <th>Insert</th>
            <th>Update</th>
            <th>Bajas</th>
            <th>Errores</th>
            <th>Aplicado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($batches as $b)
            <tr>
              <td><a href="{{ route('importaciones.show', $b->batch_id) }}">{{ $b->batch_id }}</a></td>
              <td>{{ $b->fuente }}</td>
              <td>
                <span class="badge bg-info">{{ $b->estado }}</span>
              </td>
              <td>{{ $b->total_insert }}</td>
              <td>{{ $b->total_update }}</td>
              <td>{{ $b->total_bajas ?? 0 }}</td>
              <td>{{ $b->total_errores }}</td>
              <td>{{ $b->aplicado_at }}</td>
              <td>
                <a class="btn btn-sm btn-primary" href="{{ route('importaciones.show', $b->batch_id) }}">Detalle</a>
                @if($b->estado === 'APLICADO')
                  <form method="POST" action="{{ route('personas.import.rollback', $b->batch_id) }}" style="display:inline">
                    @csrf
                    <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro de hacer rollback del batch?')">Rollback</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center">Sin registros</td></tr>
          @endforelse
        </tbody>
      </table>
      {{ $batches->links() }}
    </div>
  </div>
</div>

@endsection
