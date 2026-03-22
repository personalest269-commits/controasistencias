<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - {{ __('password_reset.forgot_title') }}</title>

    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/gentelella_custom.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/animate.min.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
</head>
<body style="background:#F7F7F7;">

<div class="">
    <div id="wrapper">
        <div class="animate form">
            <section class="login_content">
                <form method="POST" action="{{ route('password.email') }}" data-parsley-validate>
                    @csrf
                    <h1>{{ __('password_reset.forgot_title') }}</h1>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <div>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email') }}" placeholder="{{ __('password_reset.email') }}" required autofocus>
                        @error('email')
                            <div class="text-danger" style="margin-top:8px">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="margin-top:10px;">
                        <button type="submit" class="btn btn-default submit">{{ __('password_reset.send_link') }}</button>
                    </div>

                    <div class="clearfix"></div>
                    <div class="separator">
                        <p class="change_link">
                            <a href="{{ route('login') }}" class="to_register">{{ __('password_reset.back_to_login') }}</a>
                        </p>
                        <div class="clearfix"></div>
                        <br />
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

</body>
</html>
