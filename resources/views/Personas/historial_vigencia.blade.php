@extends("templates.".config("sysconfig.theme").".master")

@section("contenido")

<div class="container-fluid">
  <h1>Historial de Altas / Bajas</h1>
  <div class="mb-3">
    <a href="{{ route('personas.index') }}" class="btn btn-secondary">Volver</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div><b>Persona:</b> {{ $persona->nombres }} {{ $persona->apellido1 }} {{ $persona->apellido2 }}</div>
      <div><b>Identificación:</b> {{ $persona->identificacion }}</div>
      <div><b>Vigente actual:</b> <span class="badge bg-{{ $persona->vigente=='S'?'success':'danger' }}">{{ $persona->vigente }}</span></div>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Batch</th>
            <th>Acción</th>
            <th>Vigente Antes</th>
            <th>Vigente Después</th>
            <th>Motivo</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $l)
            @php
              $before = $l->before_json ? json_decode($l->before_json, true) : [];
              $after  = $l->after_json ? json_decode($l->after_json, true) : [];
              $vb = $before['vigente'] ?? null;
              $va = $after['vigente'] ?? null;
            @endphp
            <tr>
              <td>{{ $l->created_at }}</td>
              <td><a href="{{ route('importaciones.show', $l->batch_id) }}">{{ $l->batch_id }}</a></td>
              <td>{{ $l->accion }}</td>
              <td>{{ $vb }}</td>
              <td>{{ $va }}</td>
              <td>{{ $l->mensaje_error }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center">Sin cambios de vigencia registrados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection
