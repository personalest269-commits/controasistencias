@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestión del menú</h1>
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
                        <div>
                            <a class="btn btn-secondary" href="{{ route('OpcionMenuIndex', $soloEliminados ? [] : ['eliminados' => 1]) }}">
                                {{ $soloEliminados ? 'Ver activos' : 'Ver eliminados' }}
                            </a>
                        </div>
                        <div>
                            <a class="btn btn-primary" href="{{ route('OpcionMenuCreate') }}">Nuevo</a>
                        </div>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width:70px;">Imagen</th>
                                <th>Título</th>
                                <th>Padre</th>
                                <th>Tipo</th>
                                <th>Ruta / URL</th>
                                <th>Activo</th>
                                <th>Orden</th>
                                <th>Roles</th>
                                <th style="width:220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($opciones as $op)
                                <tr>
                                    <td>
                                        @if($op->id_archivo)
                                            <a href="{{ route('ArchivosDigitalesVer', $op->id_archivo) }}" target="_blank" title="Ver">
                                                <img src="{{ route('ArchivosDigitalesVer', $op->id_archivo) }}" style="max-width:50px; height:auto; border-radius:4px;" alt="">
                                            </a>
                                        @endif
                                    </td>
                                    <td><strong>{{ $op->titulo }}</strong></td>
                                    <td>{{ optional($op->padre)->titulo ?? '-' }}</td>
                                    <td>{{ $op->tipo === 'M' ? 'Módulo' : 'Grupo' }}</td>
                                    <td>{{ $op->url ?? '-' }}</td>
                                    <td>{{ $op->activo === 'S' ? 'S' : 'N' }}</td>
                                    <td>{{ (int)($op->orden ?? 0) }}</td>
                                    <td>
                                        @php
                                            $rs = $op->roles->map(fn($x) => optional($x->role)->name)->filter()->unique()->values()->toArray();
                                        @endphp
                                        {{ !empty($rs) ? implode(', ', $rs) : '-' }}
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="{{ route('OpcionMenuEdit', $op->id) }}">Editar</a>
                                        @if(is_null($op->estado))
                                            <form action="{{ route('OpcionMenuDelete', $op->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar esta opción? Se marcará como X (eliminación lógica).')">
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
                                    <td colspan="9" class="text-center text-muted">No hay registros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $opciones->appends(request()->query())->links() }}
                    </div>
                    <small class="text-muted">
                        Nota: Los IDs se manejan internamente. La imagen se guarda en <code>ad_archivo_digital.digital</code> (cifrado).
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
