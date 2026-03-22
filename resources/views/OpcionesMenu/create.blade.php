@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Nueva opción de menú</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
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
                <div class="card-body">
                    <form action="{{ route('OpcionMenuStore') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('OpcionesMenu._form', ['menu' => null, 'rolesSeleccionados' => []])
                        <hr>
                        <button class="btn btn-primary" type="submit">Guardar</button>
                        <a class="btn btn-light" href="{{ route('OpcionMenuIndex') }}">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
