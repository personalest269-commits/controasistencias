@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Archivos digitales</h1>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $soloEliminados ? 'Eliminados' : 'Activos' }}</strong>
                        </div>
                        <div>
                            <a class="btn btn-secondary" href="{{ route('ArchivosDigitalesIndex', $soloEliminados ? [] : ['eliminados' => 1]) }}">
                                {{ $soloEliminados ? 'Ver activos' : 'Ver eliminados' }}
                            </a>
                            <a class="btn btn-primary" href="{{ route('ArchivosDigitalesCreate') }}">
                                Subir nuevo
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width:80px;">Vista</th>
                                <th>Nombre</th>
                                <th>Tipo documento</th>
                                <th>Tipo archivo</th>
                                <th>Tamaño</th>
                                <th>Estado</th>
                                <th style="width:220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archivos as $archivo)
                                <tr>
                                    <td>
                                        @if(!empty($archivo->digital) || !empty($archivo->ruta))
                                            <a href="{{ route('ArchivosDigitalesVer', $archivo->id) }}" target="_blank" title="Ver">
                                                <img src="{{ route('ArchivosDigitalesVer', $archivo->id) }}" alt="" style="max-width:70px; height:auto; border-radius:4px;">
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <div><strong>{{ $archivo->nombre_original }}</strong></div>
                                        <div class="text-muted" style="font-size:12px;">{{ $archivo->tipo_mime }} · .{{ $archivo->extension }}</div>
                                        @if(!empty($archivo->descripcion))
                                            <div style="font-size:13px;">{{ $archivo->descripcion }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ optional($archivo->tipoDocumento)->descripcion ?? $archivo->tipo_documento_codigo ?? '-' }}
                                    </td>
                                    <td>
                                        {{ optional($archivo->tipoArchivo)->descripcion ?? $archivo->tipo_archivo_codigo ?? '-' }}
                                    </td>
                                    <td>
                                        {{ number_format(($archivo->tamano ?? 0) / 1024, 2) }} KB
                                    </td>
                                    <td>
                                        {!! is_null($archivo->estado) ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">X</span>' !!}
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="{{ route('ArchivosDigitalesEdit', $archivo->id) }}">Editar</a>
                                        <a class="btn btn-sm btn-secondary" href="{{ route('ArchivosDigitalesVer', $archivo->id) }}" target="_blank">Ver</a>
                                        @if(is_null($archivo->estado))
                                            <form action="{{ route('ArchivosDigitalesDelete', $archivo->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar este archivo? Se marcará como X (eliminación lógica).')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay registros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $archivos->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
