@extends('layouts.master')

@section('content')
<section class="content-header">
    <h1>Eventos eliminados</h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="{{ route('PgEventosIndex') }}">Eventos</a></li>
        <li class="active">Eliminados</li>
    </ol>
</section>

<section class="content">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Listado</h3>
            <div class="box-tools">
                <a class="btn btn-default btn-sm" href="{{ route('PgEventosIndex') }}">Volver</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($eventos as $e)
                        <tr>
                            <td>{{ $e->id }}</td>
                            <td>{{ $e->titulo }}</td>
                            <td>{{ \Carbon\Carbon::parse($e->fecha_inicio)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ \Carbon\Carbon::parse($e->fecha_fin)->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <form action="{{ route('PgEventosRestore', $e->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Restaurar este evento?');">
                                    @csrf
                                    <button class="btn btn-xs btn-success" type="submit">Restaurar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="box-footer">
            {{ $eventos->links() }}
        </div>
    </div>
</section>
@endsection
