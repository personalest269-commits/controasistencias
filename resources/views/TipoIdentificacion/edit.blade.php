@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Editar tipo identificación</h1>
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
                    <a href="{{ route('TipoIdentificacionIndex') }}" class="btn btn-secondary">Volver</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('TipoIdentificacionUpdate', $registro->id) }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Código</label>
                                    <input type="text" name="codigo" class="form-control" maxlength="5" value="{{ old('codigo', $registro->codigo) }}" required>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <input type="text" name="descripcion" class="form-control" maxlength="255" value="{{ old('descripcion', $registro->descripcion) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Estado actual</label>
                                    <select name="estado_actual" class="form-control">
                                        <option value="1" @if((int)old('estado_actual', $registro->estado_actual)===1) selected @endif>1</option>
                                        <option value="0" @if((int)old('estado_actual', $registro->estado_actual)===0) selected @endif>0</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Asocia persona</label>
                                    <select name="asocia_persona" class="form-control">
                                        <option value="1" @if((int)old('asocia_persona', $registro->asocia_persona)===1) selected @endif>1</option>
                                        <option value="0" @if((int)old('asocia_persona', $registro->asocia_persona)===0) selected @endif>0</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Validar</label>
                                    <select name="validar" class="form-control">
                                        <option value="1" @if((int)old('validar', $registro->validar)===1) selected @endif>1</option>
                                        <option value="0" @if((int)old('validar', $registro->validar)===0) selected @endif>0</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Longitud fija</label>
                                    <select name="longitud_fija" class="form-control">
                                        <option value="1" @if((int)old('longitud_fija', $registro->longitud_fija)===1) selected @endif>1</option>
                                        <option value="0" @if((int)old('longitud_fija', $registro->longitud_fija)===0) selected @endif>0</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Longitud</label>
                                    <input type="number" name="longitud" class="form-control" value="{{ old('longitud', $registro->longitud) }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Código SRI</label>
                                    <input type="text" name="codigo_sri" class="form-control" maxlength="10" value="{{ old('codigo_sri', $registro->codigo_sri) }}">
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
