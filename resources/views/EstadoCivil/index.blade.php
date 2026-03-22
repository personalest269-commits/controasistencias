@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Catálogo: Estado civil</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
                        <form method="GET" action="{{ route('EstadoCivilIndex') }}" class="form-inline" style="gap:8px;">
                            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por código o descripción...">
                            @if($soloEliminados)
                                <input type="hidden" name="eliminados" value="1" />
                            @endif
                            <button class="btn btn-secondary" type="submit">Buscar</button>
                            @if($q)
                                <a class="btn btn-light" href="{{ route('EstadoCivilIndex', $soloEliminados ? ['eliminados' => 1] : []) }}">Limpiar</a>
                            @endif
                        </form>

                        <div>
                            <a class="btn btn-secondary" href="{{ route('EstadoCivilIndex', $soloEliminados ? [] : ['eliminados' => 1]) }}">
                                {{ $soloEliminados ? 'Ver activos' : 'Ver eliminados' }}
                            </a>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevoEstadoCivil">Nuevo</button>
                        </div>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width:90px;">Código</th>
                                <th>Descripción</th>
                                <th style="width:220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registros as $r)
                                <tr>
                                    <td><strong>{{ $r->codigo }}</strong></td>
                                    <td>{{ $r->descripcion }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="{{ route('EstadoCivilEdit', $r->id) }}">Editar</a>
                                        @if(is_null($r->estado))
                                            <form action="{{ route('EstadoCivilDelete', $r->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar este registro? Se marcará como X (eliminación lógica).')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        @else
                                            <span class="badge badge-danger">X</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No hay registros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $registros->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Estado Civil -->
<div class="modal fade" id="modalNuevoEstadoCivil" tabindex="-1" role="dialog" aria-labelledby="modalNuevoEstadoCivilLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('EstadoCivilStore') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevoEstadoCivilLabel">Nuevo estado civil</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Código</label>
                <input type="text" name="codigo" class="form-control" maxlength="5" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <input type="text" name="descripcion" class="form-control" maxlength="255" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@stop
