@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Empresas</h4>
            <small class="text-muted">Catálogo de empresas (estado NULL = activo, X = eliminado).</small>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
                <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
                    <div class="d-flex align-items-center justify-content-between" style="gap:12px; flex-wrap:wrap;">
                        <strong>Listado</strong>
                        <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
                            <form method="GET" action="{{ route('PgEmpresasIndex') }}" class="m-0">
                                <input type="text" name="q" class="form-control form-control-sm" style="min-width:260px;" placeholder="Buscar por ID, nombre o RUC..." value="{{ $q ?? request('q') }}">
                            </form>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalNuevaEmpresa">Nueva</button>
                            <a class="btn btn-light btn-sm" href="{{ route('PgEmpresasEliminados') }}">Ver eliminadas</a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>RUC</th>
                                    <th>Teléfono</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($empresas as $e)
                                    <tr>
                                        <td>{{ $e->id }}</td>
                                        <td>{{ $e->nombre }}</td>
                                        <td>{{ $e->ruc }}</td>
                                        <td>{{ $e->telefono }}</td>
                                        <td class="text-right">
                                            <a class="btn btn-info btn-sm" href="{{ route('PgEmpresasEdit', $e->id) }}">Editar</a>
                                            <form action="{{ route('PgEmpresasDelete', $e->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta empresa?');">
                                                @csrf
                                                <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">Sin registros.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer" style="background:#fff; border-top:1px solid #e9ecef;">
                    {{ $empresas->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Nueva empresa --}}
<div class="modal fade" id="modalNuevaEmpresa" tabindex="-1" role="dialog" aria-labelledby="modalNuevaEmpresaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaEmpresaLabel">Nueva empresa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ route('PgEmpresasStore') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nombre *</label>
                                <input type="text" class="form-control" name="nombre" value="{{ old('nombre') }}" required maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>RUC</label>
                                <input type="text" class="form-control" name="ruc" value="{{ old('ruc') }}" maxlength="20">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Dirección</label>
                                <input type="text" class="form-control" name="direccion" value="{{ old('direccion') }}" maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" class="form-control" name="telefono" value="{{ old('telefono') }}" maxlength="30">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Correo</label>
                                <input type="email" class="form-control" name="correo" value="{{ old('correo') }}" maxlength="100">
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">* El ID se genera por trigger en la base de datos.</small>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
