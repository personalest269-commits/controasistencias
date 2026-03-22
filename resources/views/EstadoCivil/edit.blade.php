@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Editar estado civil</h1>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>ID #{{ $registro->id }}</strong>
                    <a href="{{ route('EstadoCivilIndex') }}" class="btn btn-secondary">Volver</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('EstadoCivilUpdate', $registro->id) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Código</label>
                                    <input type="text" name="codigo" class="form-control" maxlength="5" value="{{ old('codigo', $registro->codigo) }}" required>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <input type="text" name="descripcion" class="form-control" maxlength="255" value="{{ old('descripcion', $registro->descripcion) }}" required>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
