@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-0">Editar empresa</h4>
            <small class="text-muted">{{ $empresa->nombre }}</small>
        </div>
    </div>

    @if(session('success'))
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

    <div class="card" style="border-radius:12px; border:0; box-shadow:0 10px 25px rgba(0,0,0,.06);">
        <div class="card-header" style="background:#fff; border-bottom:1px solid #e9ecef; border-top-left-radius:12px; border-top-right-radius:12px;">
            <strong>Datos de la empresa</strong>
        </div>
        <div class="card-body">
            <form action="{{ route('PgEmpresasUpdate', $empresa->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ID</label>
                            <input type="text" class="form-control" value="{{ $empresa->id }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" class="form-control" name="nombre" value="{{ old('nombre', $empresa->nombre) }}" required maxlength="255">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>RUC</label>
                            <input type="text" class="form-control" name="ruc" value="{{ old('ruc', $empresa->ruc) }}" maxlength="20">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" class="form-control" name="direccion" value="{{ old('direccion', $empresa->direccion) }}" maxlength="255">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" class="form-control" name="telefono" value="{{ old('telefono', $empresa->telefono) }}" maxlength="30">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Correo</label>
                            <input type="email" class="form-control" name="correo" value="{{ old('correo', $empresa->correo) }}" maxlength="100">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar</button>
                <a class="btn btn-light" href="{{ route('PgEmpresasIndex') }}">Volver</a>
            </form>
        </div>
    </div>
</div>
@endsection
