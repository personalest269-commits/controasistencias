@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Catálogo: Tipo identificación</h1>
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
                        <form method="GET" action="{{ route('TipoIdentificacionIndex') }}" class="form-inline" style="gap:8px;">
                            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por código o descripción...">
                            @if($soloEliminados)
                                <input type="hidden" name="eliminados" value="1" />
                            @endif
                            <button class="btn btn-secondary" type="submit">Buscar</button>
                            @if($q)
                                <a class="btn btn-light" href="{{ route('TipoIdentificacionIndex', $soloEliminados ? ['eliminados' => 1] : []) }}">Limpiar</a>
                            @endif
                        </form>

                        <div>
                            <a class="btn btn-secondary" href="{{ route('TipoIdentificacionIndex', $soloEliminados ? [] : ['eliminados' => 1]) }}">
                                {{ $soloEliminados ? 'Ver activos' : 'Ver eliminados' }}
                            </a>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevoTipoIdentificacion">Nuevo</button>
                        </div>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width:90px;">Código</th>
                                <th>Descripción</th>
                                <th style="width:120px;">Validar</th>
                                <th style="width:120px;">Longitud</th>
                                <th style="width:120px;">SRI</th>
                                <th style="width:220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registros as $r)
                                <tr>
                                    <td><strong>{{ $r->codigo }}</strong></td>
                                    <td>{{ $r->descripcion }}</td>
                                    <td>{{ (int)$r->validar === 1 ? 'SI' : 'NO' }}</td>
                                    <td>{{ $r->longitud ?? '-' }} @if((int)$r->longitud_fija === 1)<span class="badge badge-info">Fija</span>@endif</td>
                                    <td>{{ $r->codigo_sri ?? '-' }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="{{ route('TipoIdentificacionEdit', $r->id) }}">Editar</a>
                                        @if(is_null($r->estado))
                                            <form action="{{ route('TipoIdentificacionDelete', $r->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar este registro? Se marcará como X (eliminación lógica).')">
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
                                    <td colspan="6" class="text-center text-muted">No hay registros.</td>
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

<!-- Modal Nuevo Tipo Identificación -->
<div class="modal fade" id="modalNuevoTipoIdentificacion" tabindex="-1" role="dialog" aria-labelledby="modalNuevoTipoIdentificacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="{{ route('TipoIdentificacionStore') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevoTipoIdentificacionLabel">Nuevo tipo identificación</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" name="codigo" class="form-control" maxlength="5" required>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="form-group">
                        <label>Descripción</label>
                        <input type="text" name="descripcion" class="form-control" maxlength="255" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado actual</label>
                        <select name="estado_actual" class="form-control">
                            <option value="1" selected>1</option>
                            <option value="0">0</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Asocia persona</label>
                        <select name="asocia_persona" class="form-control">
                            <option value="1">1</option>
                            <option value="0" selected>0</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Validar</label>
                        <select name="validar" class="form-control">
                            <option value="1">1</option>
                            <option value="0" selected>0</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Longitud fija</label>
                        <select name="longitud_fija" class="form-control">
                            <option value="1">1</option>
                            <option value="0" selected>0</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Longitud</label>
                        <input type="number" name="longitud" class="form-control" min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Código SRI</label>
                        <input type="text" name="codigo_sri" class="form-control" maxlength="10">
                    </div>
                </div>
            </div>
            <small class="text-muted">Eliminación lógica: estado NULL=activo, 'X'=eliminado.</small>
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
