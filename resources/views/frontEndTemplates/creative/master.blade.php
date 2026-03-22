<!DOCTYPE html>
<html lang="{{ app()->getLocale() ?? 'es' }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>{{ config('app.name') }}</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="{{ asset('frontEndTemplates/creative/assets/img/favicon.ico') }}" />
        <!-- Font Awesome (LOCAL) -->
        <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fontawesome-free/css/all.min.css') }}" />
        <!-- Google Fonts removidos para mantener todo local -->
        <!-- Third party plugin CSS (LOCAL) -->
        <link rel="stylesheet" href="{{ asset('vendor/magnific-popup/magnific-popup.min.css') }}" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="{{ asset('frontEndTemplates/creative/css/styles.css') }}" rel="stylesheet" />
    </head>
    <body id="page-top">
        @php
            // Asegurar variable $fr (parametrización frontend)
            if (!isset($fr)) {
                try { $fr = \App\Services\FrFrontedService::get(); } catch (\Throwable $e) { $fr = []; }
            }
            $pagina = $fr['pagina'] ?? null;
            $siteName = $pagina ? ($pagina->t('nombre_sitio') ?: config('app.name')) : config('app.name');
            $logoUri = null;
            if ($pagina && !empty($pagina->logo_archivo_id)) {
                $logoUri = \App\Services\ArchivoDigitalDataUriService::imageDataUri($pagina->logo_archivo_id);
            }
        @endphp
        <!-- Navigation-->
        <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
            <div class="container">
                <a class="navbar-brand js-scroll-trigger" href="{{ url('/') }}">
                    @if($logoUri)
                        <img src="{{ $logoUri }}" alt="logo" style="max-height:34px;max-width:180px;object-fit:contain;">
                    @else
                        {{ $siteName }}
                    @endif
                </a>
                <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ml-auto my-2 my-lg-0">
                        @php $items = $fr['menu'] ?? []; @endphp
                        @if(!empty($items) && count($items) > 0)
                            @foreach($items as $m)
                                @php
                                    $href = '#';
                                    if ($m->tipo === 'anchor') {
                                        $href = $m->destino ?: '#';
                                    } elseif ($m->tipo === 'route') {
                                        if (!empty($m->destino) && \Illuminate\Support\Facades\Route::has($m->destino)) {
                                            $href = route($m->destino);
                                        } else {
                                            $href = url($m->destino ?: '/');
                                        }
                                    } else {
                                        $href = $m->destino ?: '#';
                                    }
                                    $target = ($m->nuevo_tab ?? 'N') === 'S' ? '_blank' : null;
                                @endphp
                                <li class="nav-item">
                                    <a class="nav-link js-scroll-trigger" href="{{ $href }}" @if($target) target="{{ $target }}" rel="noopener" @endif>
                                        {{ $m->t() }}
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#about">{{ tr('Acerca de') }}</a></li>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#services">{{ tr('Servicios') }}</a></li>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#portfolio">{{ tr('Portafolio') }}</a></li>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="{{ route('blogs') }}">{{ tr('Blog') }}</a></li>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#contact">{{ tr('Contacto') }}</a></li>
                        @endif

                        {{-- Selector de idioma (frontend) --}}
                        @php
                            $currentLang = app()->getLocale() ?: (session('lang') ?: 'es');
                            $idiomasList = isset($idiomas) ? $idiomas : collect();
                        @endphp
                        @if($idiomasList && count($idiomasList) > 0)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    🌐 {{ strtoupper($currentLang) }}
                                </a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="langDropdown">
                                    @foreach($idiomasList as $idioma)
                                        @php $code = strtolower((string)($idioma->codigo ?? 'es')); @endphp
                                        <a class="dropdown-item" href="{{ route('lang.change', $code) }}">
                                            {{ $idioma->nombre }}
                                        </a>
                                    @endforeach
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
        @section('content')
        @show
        <!-- Footer-->
        <footer class="bg-light py-5">
            <div class="container"><div class="small text-center text-muted"></div></div>
        </footer>
        <!-- Bootstrap core JS-->
        <script src="{{ asset('admin_lte/plugins/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('admin_lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <!-- Third party plugin JS-->
        <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
        <script src="{{ asset('vendor/magnific-popup/jquery.magnific-popup.min.js') }}"></script>
        <!-- Core theme JS-->
        <script src="{{ asset('frontEndTemplates/creative/js/scripts.js') }}"></script>
    </body>
</html>