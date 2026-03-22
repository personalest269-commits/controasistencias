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
        <!-- Navigation-->
        <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3 navbar-scrolled" id="mainNav">
            <div class="container">
                <a class="navbar-brand js-scroll-trigger" href="{{ route('site') }}">{{ config('app.name') }}</a>
                <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ml-auto my-2 my-lg-0">
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#about">About</a></li>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#services">Services</a></li>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#portfolio">Portfolio</a></li>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#Blog">Blog</a></li>
                        <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#contact">Contact</a></li>
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
<!--        <script src="{{ asset('frontEndTemplates/creative/js/scripts.js') }}"></script>-->
    </body>
</html>