<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }}</title>

  <!-- Google Font: Source Sans Pro -->
  {{-- Google Font removido para mantener todo local --}}
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fontawesome-free/css/all.min.css') }}">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('admin_lte/dist/css/adminlte.min.css') }}">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    @php($reg = (($pgcfg['REGISTRO_USUARIO_ACTIVO'] ?? 'S') === 'S'))
    @php
      $recSite = trim((string) ($pgcfg['RECAPTCHA_SITE_KEY'] ?? ''));
      $recSecret = trim((string) ($pgcfg['RECAPTCHA_SECRET_KEY'] ?? ''));
      $recEnabled = ($recSite !== '' && $recSecret !== '');
    @endphp
    @if(!empty($app_logo_url))
      <div style="margin-bottom:8px;">
        <img src="{{ $app_logo_url }}" alt="logo" style="max-height:70px; max-width:240px;">
      </div>
    @endif
    <a href="#">{{ config('app.name') }}</a>
  </div>
  <!-- /.login-logo -->
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Sign in to start your session</p>
        <form method="POST" action="{!! route('loginPost') !!}" data-parsley-validate >
            {!! csrf_field() !!}
        <div class="input-group mb-3">
          <input type="text" name="login_usuario" value="{{ old('login_usuario') }}" class="form-control col-md-12" placeholder="Usuario" />
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
          @forelse($errors->get('login_usuario') as $Email)
          <label class="alert alert-danger alert-dismissible col-md-12">{{ $Email }}</label>
          @empty
          @endforelse
        </div>
        <div class="input-group mb-3">
          <input type="password" name="login_password" class="form-control" placeholder="Password"  />
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        @forelse($errors->get('login_password') as $Password)
        <label class="alert alert-danger alert-dismissible col-md-12">{{ $Password }}</label>
        @empty
        @endforelse
        </div>

        @if($recEnabled)
          <div class="mb-3">
            <div class="g-recaptcha" data-sitekey="{{ $recSite }}"></div>
            @forelse($errors->get('g-recaptcha-response') as $capErr)
              <label class="alert alert-danger alert-dismissible col-md-12">{{ $capErr }}</label>
            @empty
            @endforelse
            @forelse($errors->get('recaptcha') as $capErr2)
              <label class="alert alert-danger alert-dismissible col-md-12">{{ $capErr2 }}</label>
            @empty
            @endforelse
          </div>
        @endif
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">
                Remember Me
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          </div>
          <!-- /.col -->
        </div>
      </form>

      <div class="social-auth-links text-center mb-3">
        <p>- OR -</p>
        <a href="{{ route('facebookLogin') }}" class="btn btn-block btn-primary">
          <i class="fab fa-facebook mr-2"></i> Sign in using Facebook
        </a>
        <a href="{{ route('twitterLogin') }}" class="btn btn-block btn-primary">
          <i class="fab fa-twitter mr-2"></i> Sign in using Twitter
        </a>
        <a href="{{ route('googleLogin') }}" class="btn btn-block btn-danger">
          <i class="fab fa-google-plus mr-2"></i> Sign in using Google+
        </a>
      </div>
      <!-- /.social-auth-links -->

      <p class="mb-1">
        <a href="{{ route('password.request') }}">I forgot my password</a>
      </p>
      @if($reg)
        <p class="mb-0">
          <a href="{{ route('register') }}" class="text-center">Register a new membership</a>
        </p>
      @endif
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="{{ asset('admin_lte/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('admin_lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('admin_lte/dist/js/adminlte.min.js') }}"></script>

@if($recEnabled)
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
</body>
</html>
