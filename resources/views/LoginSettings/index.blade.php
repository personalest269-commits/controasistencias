@extends("templates.".config("sysconfig.theme").".master")

@section('content')
<div class="">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Configuración de Login</h1>
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
                <div class="card-body">
                    <form method="POST" action="{{ route('login-settings.update') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-lg-4">
                                <h5 class="mb-3">Imágenes</h5>

                                <div class="form-group">
                                    <label>Logo del sistema (Login)</label>
                                    <input type="file" class="form-control" name="logo_sistema" accept="image/*">
                                    @if(!empty($logoUrl))
                                        <div class="mt-2">
                                            <img src="{{ $logoUrl }}" alt="logo" style="max-height:70px; max-width:240px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:6px;">
                                        </div>
                                    @endif
                                    <small class="text-muted">Se guarda en pg_configuraciones: <b>LOGO_SISTEMA</b></small>
                                </div>

                                <div class="form-group mt-3">
                                    <label>Modo de carga de imágenes del login</label>
                                    <select class="form-control" name="login_image_mode">
                                        <option value="route" @if(($loginImageMode ?? 'route')==='route') selected @endif>Route (recomendado) - /admin/public-file/{{'{id}'}}</option>
                                        <option value="base64" @if(($loginImageMode ?? 'route')==='base64') selected @endif>Base64 (Data URI) - incrustado en HTML</option>
                                    </select>
                                    <small class="text-muted">Se guarda en pg_configuraciones: <b>LOGIN_IMAGE_MODE</b> (route|base64)</small>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label>Ilustración izquierda</label>
                                    <input type="file" class="form-control" name="login_illus_left" accept="image/*">
                                    @if(!empty($loginLeft))
                                        <div class="mt-2">
                                            <img src="{{ $loginLeft }}" alt="left" style="max-height:220px; max-width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:6px;">
                                        </div>
                                    @endif
                                    <small class="text-muted">Se guarda en pg_configuraciones: <b>LOGIN_ILLUS_LEFT</b></small>
                                </div>

                                <div class="form-group">
                                    <label>Ilustración derecha</label>
                                    <input type="file" class="form-control" name="login_illus_right" accept="image/*">
                                    @if(!empty($loginRight))
                                        <div class="mt-2">
                                            <img src="{{ $loginRight }}" alt="right" style="max-height:220px; max-width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:6px;">
                                        </div>
                                    @endif
                                    <small class="text-muted">Se guarda en pg_configuraciones: <b>LOGIN_ILLUS_RIGHT</b></small>
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <h5 class="mb-3">Contenidos (se muestran en modal)</h5>
                                <p class="text-muted mb-3">Estos textos se guardan en <b>pg_general_traduccion</b> como HTML. Puedes definirlos por idioma.</p>

                                <ul class="nav nav-tabs" role="tablist">
                                    @foreach($idiomas as $idx => $i)
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link @if($idx===0) active @endif" id="tab-{{ $i->codigo }}" data-toggle="tab" data-bs-toggle="tab" href="#pane-{{ $i->codigo }}" role="tab">{{ $i->nombre }} ({{ $i->codigo }})</a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content" style="border:1px solid #dee2e6; border-top:0; padding:15px; border-radius:0 0 8px 8px;">
                                    @foreach($idiomas as $idx => $i)
                                        @php($lang = $i->codigo)
                                        <div class="tab-pane fade @if($idx===0) show active @endif" id="pane-{{ $lang }}" role="tabpanel">

                                            <div class="form-group">
                                                <label>Sobre Nosotros</label>
                                                <textarea class="form-control" rows="5" name="texts[{{ $lang }}][login.about.body]">{{ old("texts.$lang.login.about.body", $texts[$lang]['login.about.body'] ?? '') }}</textarea>
                                                <small class="text-muted">Clave: <b>login.about.body</b></small>
                                            </div>

                                            <div class="form-group">
                                                <label>Términos y Condiciones</label>
                                                <textarea class="form-control" rows="5" name="texts[{{ $lang }}][login.terms.body]">{{ old("texts.$lang.login.terms.body", $texts[$lang]['login.terms.body'] ?? '') }}</textarea>
                                                <small class="text-muted">Clave: <b>login.terms.body</b></small>
                                            </div>

                                            <div class="form-group">
                                                <label>Política de Privacidad</label>
                                                <textarea class="form-control" rows="5" name="texts[{{ $lang }}][login.privacy.body]">{{ old("texts.$lang.login.privacy.body", $texts[$lang]['login.privacy.body'] ?? '') }}</textarea>
                                                <small class="text-muted">Clave: <b>login.privacy.body</b></small>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
