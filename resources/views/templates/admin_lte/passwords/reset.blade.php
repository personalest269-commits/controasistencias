<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }} - {{ __('password_reset.title') }}</title>

  {{-- Google Font removido para mantener todo local --}}
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('admin_lte/dist/css/adminlte.min.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="{{ route('login') }}">{{ config('app.name') }}</a>
  </div>

  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">{{ __('password_reset.reset_subtitle') }}</p>

      <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="input-group mb-3">
          <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}"
                 class="form-control @error('email') is-invalid @enderror"
                 placeholder="{{ __('password_reset.email') }}" required autofocus>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        @error('email')
          <div class="text-danger mb-3">{{ $message }}</div>
        @enderror

        <div class="input-group mb-3">
          <input id="password" type="password" name="password"
                 class="form-control @error('password') is-invalid @enderror"
                 placeholder="{{ __('password_reset.password') }}" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        @error('password')
          <div class="text-danger mb-3">{{ $message }}</div>
        @enderror

        <div class="input-group mb-3">
          <input id="password_confirmation" type="password" name="password_confirmation"
                 class="form-control"
                 placeholder="{{ __('password_reset.confirm_password') }}" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">{{ __('password_reset.reset_button') }}</button>
          </div>
        </div>
      </form>

      <p class="mt-3 mb-1">
        <a href="{{ route('login') }}">{{ __('password_reset.back_to_login') }}</a>
      </p>
    </div>
  </div>
</div>

<script src="{{ asset('admin_lte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('admin_lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('admin_lte/dist/js/adminlte.min.js') }}"></script>
</body>
</html>
