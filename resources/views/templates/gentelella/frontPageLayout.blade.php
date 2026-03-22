<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <!-- Bootstrap core CSS -->
    <link href="<?php echo asset('assets/css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('assets/css/gentelella_custom.css'); ?>" rel="stylesheet">
    <script src="<?php echo asset('assets/js/jquery.min.js') ?>"></script>
    <link href="<?php echo asset('assets/css/animate.min.css'); ?>" rel="stylesheet">
    
</head>

<body style="background:#F7F7F7;">
    
    <div class="">
        <a class="hiddenanchor" id="toregister"></a>
        <a class="hiddenanchor" id="tologin"></a>
        <div id="wrapper">
            <div id="login" class="animate form">
                <section class="login_content">
                    <form method="POST" action="{!! route('loginPost') !!}" data-parsley-validate >
                         {!! csrf_field() !!}
                        @php($reg = (($pgcfg['REGISTRO_USUARIO_ACTIVO'] ?? 'S') === 'S'))
                        @php
                            $recSite = trim((string) ($pgcfg['RECAPTCHA_SITE_KEY'] ?? ''));
                            $recSecret = trim((string) ($pgcfg['RECAPTCHA_SECRET_KEY'] ?? ''));
                            $recEnabled = ($recSite !== '' && $recSecret !== '');
                        @endphp
                        @if(!empty($app_logo_url))
                            <div style="margin:0 0 10px 0;">
                                <img src="{{ $app_logo_url }}" alt="logo" style="max-height:70px; max-width:240px;">
                            </div>
                        @endif
                        <h1>{{ config('app.name') }}</h1>
                        <div class="col-md-12" style="margin-bottom: 10px">
                            <div class="col-md-4">
                                <a href="{{ route('facebookLogin') }}"><image src="{{ asset('photos/facebook.png') }}"  /></a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('twitterLogin') }}"><image src="{{ asset('photos/twitter.png') }}"  /></a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('googleLogin') }}"><image src="{{ asset('photos/google.png') }}" /></a>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <input type="text" name="login_usuario" value="{{ old('login_usuario') }}" class="form-control col-md-12" placeholder="Usuario" />
                                @forelse($errors->get('login_usuario') as $Email)
                                <label class="alert alert-danger alert-dismissible col-md-12">{{ $Email }}</label>
                                @empty
                                @endforelse
                            <input type="password" name="login_password" class="form-control" placeholder="Password"  />
                                @forelse($errors->get('login_password') as $Password)
                                <label class="alert alert-danger alert-dismissible col-md-12">{{ $Password }}</label>
                                @empty
                                @endforelse    

                            @if($recEnabled)
                                <div style="margin-top:10px;">
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
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-default submit">Submit</button>
                            <input type="checkbox" name="remember"> Remember Me
                            <a class="reset_pass" href="{{ route('password.request') }}">Lost your password?</a>
                        </div>
                        <div class="clearfix"></div>
                        @if($reg)
                        <div class="separator">

                            <p class="change_link">New to site?
                                <a href="#toregister" class="to_register"> Create Account </a>
                            </p>
                            <div class="clearfix"></div>
                            <br />
                        </div>
                        @endif
                    </form>
                    <!-- form -->
                </section>
                <!-- content -->
            </div>
            @if($reg)
            <div id="register" class="animate form">
                <section class="login_content">
                    <form method="POST" action="{!! route('registerPost') !!}" data-parsley-validate>
                        {!! csrf_field() !!}
                        <h1>Create Account</h1>
                        <div class="col-md-12">
                            <input type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" placeholder="First Name" />
                            @forelse($errors->get('first_name') as $first_name)
                                <label class="alert alert-danger alert-dismissible col-md-12">{{ $first_name }}</label>
                            @empty
                            @endforelse
                        </div>
                        <div class="col-md-12">
                            <input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" placeholder="Last Name" />
                            @forelse($errors->get('last_name') as $last_name)
                                <label class="alert alert-danger alert-dismissible col-md-12">{{ $last_name }}</label>
                            @empty
                            @endforelse
                        </div>
                        <div class="col-md-12">
                            <input type="text" class="form-control" name="email" value="{{ old('email') }}" placeholder="Username (Your email address)" />
                            @forelse($errors->get('email') as $email)
                                <label class="alert alert-danger alert-dismissible col-md-12">{{ $email }}</label>
                            @empty
                            @endforelse
                        </div>
                        <div class="col-md-12">
                            <input type="password" class="form-control" name="password" placeholder="Password" />
                            @forelse($errors->get('password') as $password)
                                <label class="alert alert-danger alert-dismissible col-md-12">{{ $password }}</label>
                            @empty
                            @endforelse
                        </div>
                        <div>
                            <button type="submit" class="btn btn-default submit">Submit</button>
                        </div>
                        <div class="clearfix"></div>
                        <div class="separator">

                            <p class="change_link">Already a member ?
                                <a href="#tologin" class="to_register"> Log in </a>
                            </p>
                            <div class="clearfix"></div>
                        </div>
                    </form>
                    <!-- form -->
                </section>
                <!-- content -->
            </div>
            @endif
            <div id="passwordreset" class="animate form">
                <section class="login_content">
                    <form method="POST" action="{{ url('/password/reset') }}" data-parsley-validate>
                        {!! csrf_field() !!}
                        <h1>Password Reset</h1>
                        <div>
                            <input type="email" class="form-control" name="email" placeholder="Email" required="" />
                        </div>
                        <div>
                            <button type="submit" class="btn btn-default submit">Submit</button>
                        </div>
                        <div class="clearfix"></div>
                        <div class="separator">

                            <p class="change_link">Already a member ?
                                <a href="#tologin" class="to_register"> Log in </a>
                            </p>
                            <div class="clearfix"></div>
                        </div>
                    </form>
                    <!-- form -->
                </section>
                <!-- content -->
            </div>
        </div>
    </div>
</body>

@if(isset($recEnabled) && $recEnabled)
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
</html>