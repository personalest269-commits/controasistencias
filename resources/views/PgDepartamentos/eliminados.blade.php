@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Departamentos eliminados</h4>
            <small class="text-muted">Puedes restaurar registros eliminados lógicamente.</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
            <div class="d-flex justify-content-between align-items-center">
                <strong>Listado</strong>
                <a class="btn btn-light btn-sm" href="{{ route('PgDepartamentosIndex') }}">Volver</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departamentos as $d)
                            <tr>
                                <td>{{ $d->id }}</td>
                                <td>{{ $d->codigo }}</td>
                                <td>{{ $d->descripcion }}</td>
                                <td class="text-right">
                                    <form action="{{ route('PgDepartamentosRestore', $d->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Restaurar este departamento?');">
                                        @csrf
                                        <button class="btn btn-success btn-sm" type="submit">Restaurar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Sin registros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer" style="background:#fff; border-top:1px solid #e9ecef;">
            {{ $departamentos->links() }}
        </div>
    </div>
</div>
@endsection
