<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - {{ __('password_reset.title') }}</title>

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
                <form method="POST" action="{{ route('password.update') }}" data-parsley-validate>
                    @csrf
                    <h1>{{ __('password_reset.title') }}</h1>

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                               name="email" value="{{ $email ?? old('email') }}" placeholder="{{ __('password_reset.email') }}" required autofocus>
                        @error('email')
                            <div class="text-danger" style="margin-top:8px">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="margin-top:10px;">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                               name="password" placeholder="{{ __('password_reset.password') }}" required>
                        @error('password')
                            <div class="text-danger" style="margin-top:8px">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="margin-top:10px;">
                        <input id="password_confirmation" type="password" class="form-control"
                               name="password_confirmation" placeholder="{{ __('password_reset.confirm_password') }}" required>
                    </div>

                    <div style="margin-top:10px;">
                        <button type="submit" class="btn btn-default submit">{{ __('password_reset.reset_button') }}</button>
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
