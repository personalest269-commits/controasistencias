@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Empresas eliminadas</h4>
            <small class="text-muted">Listado de empresas con estado = 'X'.</small>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
            <div class="d-flex align-items-center justify-content-between" style="gap:12px; flex-wrap:wrap;">
                <strong>Eliminadas</strong>
                <a class="btn btn-light btn-sm" href="{{ route('PgEmpresasIndex') }}">Volver</a>
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
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($empresas as $e)
                            <tr>
                                <td>{{ $e->id }}</td>
                                <td>{{ $e->nombre }}</td>
                                <td>{{ $e->ruc }}</td>
                                <td class="text-right">
                                    <form action="{{ route('PgEmpresasRestore', $e->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Restaurar esta empresa?');">
                                        @csrf
                                        <button class="btn btn-success btn-sm" type="submit">Restaurar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No hay eliminadas.</td></tr>
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
@endsection
