<!doctype html>
<html lang="{{ app()->getLocale() ?? 'es' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }} - Acceso</title>

  <!-- Bootstrap 5 (LOCAL) -->
  <link rel="stylesheet" href="{{ asset('vendor/login/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/login/all.min.css') }}">
   <link rel="stylesheet" href="{{ asset('vendor/login/css2.css') }}">
  


  <!-- Font Awesome (CDN) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Fuente: Inter (Google Fonts) removido para mantener todo 100% local. -->
 <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
    <style>
    :root{
      --control-blue:#0b86c3;
      --control-blue-2:#0a7ab1;
      --control-gray:#6b7280;
      --control-border:#e5e7eb;
    }

    html,body{height:100%;}
    body{
      font-family: system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
      background:#fff;
      overflow-x:hidden;
    }

    /* split background */
    .control-bg{
      position:relative;
      min-height:100vh;
      background:#fff;
    }
    .control-bg::after{
      content:"";
      position:absolute;
      top:0; right:0;
      width:50%; height:100%;
      background:linear-gradient(180deg,var(--control-blue),var(--control-blue-2));
      z-index:0;
    }

    .topbar{
      position:relative;
      z-index:2;
      background:transparent;
    }

    /* Floating white bar like the reference screenshot */
    .topbar-shell{
      background:#fff;
      border:1px solid rgba(229,231,235,.95);
      border-radius:14px;
      box-shadow:0 10px 30px rgba(17,24,39,.08);
      /* a touch taller, like the reference */
      padding:.75rem 1.25rem;
    }
    .brand{
      display:flex;
      align-items:center;
      gap:.6rem;
      font-weight:700;
      letter-spacing:.2px;
    }
    .brand .mark{
      width:42px;height:42px;
      display:grid;place-items:center;
      border-radius:999px;
      background:rgba(11,134,195,.10);
      color:var(--control-blue);
      font-size:18px;
    }
    /* Logo size like the reference */
    .brand img{max-height:54px; max-width:280px;}

    .top-links a,
    .top-links .nav-link{
      color:#111827;
      text-decoration:none;
      font-weight:500;
      font-size:.95rem;
    }
    .top-links a:hover,
    .top-links .nav-link:hover{color:var(--control-blue);}

    /* Pipe separators between links (desktop) */
    @media (min-width: 768px){
      .top-links .nav-item + .nav-item{
        position:relative;
        padding-left:1.15rem;
      }
      .top-links .nav-item + .nav-item::before{
        content:"|";
        position:absolute;
        left:.45rem;
        top:50%;
        transform:translateY(-50%);
        color:#9ca3af;
        font-weight:600;
      }
    }

    .navbar-toggler{
      border:1px solid rgba(229,231,235,.95);
      border-radius:12px;
      padding:.4rem .55rem;
    }
    .navbar-toggler:focus{box-shadow:0 0 0 .2rem rgba(11,134,195,.15);}

    .lang-select{
      border:1px solid var(--control-border);
      border-radius:10px;
      padding:.45rem .65rem;
      background:#fff;
      font-weight:600;
      font-size:.9rem;
    }

    /* main grid */
    .main-wrap{
      position:relative;
      z-index:1;
      padding:2.5rem 0 6rem;
    }

    .illus-left{
      max-width:520px;
      opacity:.95;
      margin-top:1.25rem;
    }

    .login-card{
      width:min(420px, 92vw);
      border-radius:18px;
      border:1px solid rgba(229,231,235,.85);
      box-shadow:0 18px 50px rgba(17,24,39,.10);
      background:#fff;
    }
    .login-card .card-body{padding:2rem 2rem 1.7rem;}
    .login-title{font-size:1.6rem;font-weight:700;margin-bottom:1.2rem;}

    .form-control{
      border-radius:12px;
      padding:.72rem .95rem;
      border:1px solid rgba(229,231,235,.95);
    }
    .form-control:focus{
      border-color:rgba(11,134,195,.55);
      box-shadow:0 0 0 .2rem rgba(11,134,195,.15);
    }

    .btn-control{
      background:var(--control-blue);
      border-color:var(--control-blue);
      border-radius:12px;
      padding:.72rem 1rem;
      font-weight:700;
    }
    .btn-control:hover{background:var(--control-blue-2); border-color:var(--control-blue-2);}

    .muted-link{color:var(--control-blue); text-decoration:none; font-weight:600;}
    .muted-link:hover{text-decoration:underline;}

    /* bottom illustration (right) */
    .illus-right{
      position:absolute;
      right:2.5rem;
      bottom:2.75rem;
      width:min(460px, 38vw);
      opacity:.95;
      z-index:1;
      pointer-events:none;
    }

    /* cookie bar */
    .cookie-bar{
      position:fixed;
      left:50%;
      transform:translateX(-50%);
      bottom:18px;
      width:min(860px, 96vw);
      z-index:9999;
      background:#fff;
      border:1px solid rgba(229,231,235,.95);
      border-radius:16px;
      box-shadow:0 18px 45px rgba(17,24,39,.18);
      padding:16px 18px;
      display:none;
    }
    .cookie-bar h6{margin:0 0 6px; font-weight:800;}
    .cookie-bar p{margin:0; color:#4b5563; font-size:.92rem;}

    /* Buttons stacked like the reference (desktop & mobile) */
    .cookie-actions{flex-direction:column; width:190px;}

    .cookie-actions .btn{
      border-radius:12px;
      padding:.55rem .9rem;
      font-weight:700;
      font-size:.9rem;
      width:100%;
    }

    @media (max-width: 575px){
      .cookie-actions{width:100%;}
    }

    /* modal */
    .modal-content{border-radius:18px; border:1px solid rgba(229,231,235,.95);}
    .pref-row{
      border:1px solid rgba(229,231,235,.95);
      border-radius:14px;
      padding:12px 14px;
      background:#f9fafb;
    }

    @media (max-width: 991px){
      .control-bg::after{width:100%; height:42%; top:auto; bottom:0;}
      .illus-right{display:none;}
      .main-wrap{padding-bottom:7rem;}
    }

    @media (max-width: 767px){
      .topbar-shell{padding:.5rem .75rem;}
      .navbar-nav{padding:.5rem 0;}
      .lang-select{width:100%;}
    }
  </style>
</head>
<body>
  @php
    // Compatibilidad: algunos ResponseController pasan variables directas y otros dentro de $data
    $idiomas = $data['idiomas'] ?? ($idiomas ?? []);
    $legal = $data['legal'] ?? ($legal ?? ['about'=>'<p>---</p>','terms'=>'<p>---</p>','privacy'=>'<p>---</p>']);
    $assets = $data['assets'] ?? ($assets ?? []);
    // Variables compartidas desde AppServiceProvider (view()->share)
    // y compatibilidad con el arreglo $assets en caso de que exista.
    $app_logo_url = $assets['logo_url'] ?? ($app_logo_url ?? null);
    $illusLeft = $assets['illus_left'] ?? ($login_illus_left_url ?? null);
    $illusRight = $assets['illus_right'] ?? ($login_illus_right_url ?? null);

    // Textos del menú legal (claves fijas para administrarlas/ traducirlas)
    $tAboutTitle = pg_t('login.nav.about', 'Sobre Nosotros');
    $tTermsTitle = pg_t('login.nav.terms', 'Términos y Condiciones');
    $tPrivacyTitle = pg_t('login.nav.privacy', 'Política de Privacidad');
    $tLoginTitle = tr('Acceso');
    $tEmail = tr('Correo electrónico');
    $tPassword = tr('Contraseña');
    $tForgot = tr('¿Olvidaste tu contraseña?');
    $tNoAccount = tr('¿No tienes una cuenta?');
    $tRegister = tr('Registro');
    $tEnterEmail = tr('Ingrese su correo electrónico');
    $tEnterPass = tr('Ingrese su contraseña');
    $tCookieTitle = tr('¡Usamos cookies!');
    $tCookieBody = tr('Hola, este sitio web utiliza cookies esenciales para garantizar su correcto funcionamiento y cookies de seguimiento para comprender cómo interactúas con él.');
    $tLetChoose = tr('Déjame elegir');
    $tAcceptAll = tr('Aceptar todo');
    $tRejectAll = tr('Rechazar todo');
    $tCookiePref = tr('Cookie preferences');
    $tStrict = tr('Cookies estrictamente necesarias');
    $tStrictDesc = tr('Siempre activas. Requeridas para el funcionamiento del sitio.');
    $tMoreInfo = tr('Para cualquier consulta en relación con nuestra política de cookies y sus opciones, póngase en contacto con nosotros.');
    $tContact = tr('contact us');
  @endphp

  @php
    // Captcha (reCAPTCHA v2): se habilita SOLO si existen ambas llaves en pg_configuraciones.
    $recSite = trim((string) ($pgcfg['RECAPTCHA_SITE_KEY'] ?? ''));
    $recSecret = trim((string) ($pgcfg['RECAPTCHA_SECRET_KEY'] ?? ''));
    $recEnabled = ($recSite !== '' && $recSecret !== '');
  @endphp

  <div class="control-bg">

    <!-- Top Bar (responsive) -->
    <div class="topbar">
      <div class="container py-3">
        <div class="topbar-shell">
          <nav class="navbar navbar-expand-md navbar-light p-0">
          <a class="navbar-brand brand m-0" href="#" aria-label="home">
            @if(!empty($app_logo_url))
              <img src="{{ $app_logo_url }}" alt="logo">
            @else
              <div class="mark"><i class="fa-solid fa-gear"></i></div>
              <div>{{ config('app.name') }}</div>
            @endif
          </a>

          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>

          <div class="collapse navbar-collapse" id="topNav">
            <div class="d-md-flex align-items-md-center w-100">
              <ul class="navbar-nav align-items-md-center top-links mx-md-auto">
              <li class="nav-item"><a class="nav-link px-md-0 js-legal" href="#" data-legal="about">{{ $tAboutTitle }}</a></li>
              <li class="nav-item"><a class="nav-link px-md-0 js-legal" href="#" data-legal="terms">{{ $tTermsTitle }}</a></li>
              <li class="nav-item"><a class="nav-link px-md-0 js-legal" href="#" data-legal="privacy">{{ $tPrivacyTitle }}</a></li>
              </ul>

              <form method="POST" action="{{ route('setLang') }}" id="langForm" class="ms-md-auto mt-2 mt-md-0">
              @csrf
              <select class="lang-select" id="langSelect" name="lang" onchange="document.getElementById('langForm').submit()">
                @php($curLang = session('lang', app()->getLocale() ?? 'es'))
                @forelse($idiomas as $it)
                  <option value="{{ $it->codigo }}" @if($curLang===$it->codigo) selected @endif>
                    {{ $it->nombre }}
                  </option>
                @empty
                  <option value="es" @if($curLang==='es') selected @endif>Español</option>
                  <option value="en" @if($curLang==='en') selected @endif>English</option>
                @endforelse
              </select>
              </form>
            </div>
          </div>
          </nav>
        </div>
      </div>
    </div>

    <div class="main-wrap">
      <div class="container">
        <!--
          Layout objetivo (como el modelo de CONTROL):
          - Ilustración a la izquierda
          - Card de login centrado en la pantalla
          - Lado derecho libre para el fondo azul + ilustración derecha (absoluta)
        -->
        <div class="row align-items-center g-5">

          <!-- Left illustration / whitespace (like screenshot)
               En responsive (<=991px) se oculta para que NO se muestre la imagen de fondo, como en la referencia -->
          <div class="col-lg-4 d-none d-lg-block">
            <div class="illus-left">
              @if(!empty($illusLeft))
                <img src="{{ $illusLeft }}" alt="Ilustración" style="max-width:100%; height:auto;">
              @else
              <img src="{{ asset('uploads/login/default_left.png') }}" alt="Ilustración" style="max-width:100%; height:auto;">
              @endif
            </div>
          </div>

          <!-- Login card (centrado) -->
          <div class="col-lg-4 d-flex justify-content-center">
            <div class="login-card card">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="login-title">{{ $tLoginTitle }}</div>
                </div>

                <form method="POST" action="{{ route('loginPost') }}">
                  @csrf

                  <div class="mb-3">
                    <label class="form-label">{{ tr('Usuario') }}</label>
                    <div class="input-group">
                      <input type="text" name="login_usuario" id="login_usuario" value="{{ old('login_usuario') }}" class="form-control" placeholder="Usuario" required>
                      <span class="input-group-text bg-white" id="usuarioHelpIcon" title="Cédula / RUC / Pasaporte" style="cursor:help">
                        <i class="fa-solid fa-circle-info"></i>
                      </span>
                    </div>
                    <div class="small text-muted mt-1" id="usuarioHint">Cédula (10) / RUC (13) / Pasaporte</div>
                    @if($errors->has('login_usuario'))
                      <div class="text-danger small mt-1">{{ $errors->first('login_usuario') }}</div>
                    @endif
                  </div>

                  <div class="mb-2">
                    <label class="form-label">{{ $tPassword }}</label>
                    <div class="input-group">
                      <input type="password" name="login_password" id="login_password" class="form-control" placeholder="{{ $tEnterPass }}" required>
                      <button class="btn btn-light border" type="button" id="togglePass" aria-label="toggle password">
                        <i class="fa-regular fa-eye"></i>
                      </button>
                    </div>
                    @if($errors->has('login_password'))
                      <div class="text-danger small mt-1">{{ $errors->first('login_password') }}</div>
                    @endif
                  </div>

                  @if($recEnabled)
                    <div class="mb-3">
                      <div class="g-recaptcha" data-sitekey="{{ $recSite }}"></div>
                      @if($errors->has('g-recaptcha-response'))
                        <div class="text-danger small mt-1">{{ $errors->first('g-recaptcha-response') }}</div>
                      @endif
                      @if($errors->has('recaptcha'))
                        <div class="text-danger small mt-1">{{ $errors->first('recaptcha') }}</div>
                      @endif
                    </div>
                  @endif

                  <div class="d-flex justify-content-start mb-3">
                    <a class="muted-link" href="{{ route('password.request') }}">{{ $tForgot }}</a>
                  </div>

                  <button type="submit" class="btn btn-control w-100 text-white">{{ $tLoginTitle }}</button>

                  @if($errors->has('login_error'))
                    <div class="text-danger mt-2 fw-semibold">{{ $errors->first('login_error') }}</div>
                  @endif

                  @php($reg = (($pgcfg['REGISTRO_USUARIO_ACTIVO'] ?? 'S') === 'S'))
                  @if($reg)
                    <div class="text-center mt-3" style="color:var(--control-gray)">
                      {{ $tNoAccount }} <a class="muted-link" href="{{ route('register') }}">{{ $tRegister }}</a>
                    </div>
                  @endif
                </form>
              </div>
            </div>
          </div>

          <!-- Spacer column (mantiene el card centrado en desktop) -->
          <div class="col-lg-4 d-none d-lg-block"></div>

        </div>
      </div>

      <!-- Decorative right illustration (like screenshot) -->
      <div class="illus-right">
        @if(!empty($illusRight))
          <img src="{{ $illusRight }}" alt="Ilustración" style="max-width:100%; height:auto;">
        @else
        <img src="{{ asset('uploads/login/default_right.png') }}" alt="Ilustración" style="max-width:100%; height:auto;">
        @endif
      </div>
    </div>

    <!-- Cookie bar -->
    <div class="cookie-bar" id="cookieBar" style="{{ request()->cookie('cookie_consent') ? 'display:none' : 'display:flex' }}">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div>
          <h6>{{ $tCookieTitle }}</h6>
          <p>
            {{ $tCookieBody }} <a href="#" class="muted-link" id="cookieChoose">{{ $tLetChoose }}</a>
          </p>
        </div>
        <div class="cookie-actions d-flex gap-2">
          <button class="btn btn-dark" id="cookieAccept">{{ $tAcceptAll }}</button>
          <button class="btn btn-light border" id="cookieReject">{{ $tRejectAll }}</button>
        </div>
      </div>
    </div>

    <!-- Cookie preferences modal -->
    <div class="modal fade" id="cookieModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width:720px;">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold">{{ $tCookiePref }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body pt-0">
            <p class="text-muted mb-3">{{ $tCookieTitle }} {{ $tCookieBody }}</p>

            <div class="pref-row d-flex align-items-center justify-content-between gap-3">
              <div>
                <div class="fw-bold">{{ $tStrict }}</div>
                <div class="text-muted" style="font-size:.92rem;">{{ $tStrictDesc }}</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" checked disabled>
              </div>
            </div>

            <div class="mt-3">
              <div class="text-muted" style="font-size:.95rem;">
                {{ $tMoreInfo }} <a class="muted-link" href="#">{{ $tContact }}</a>.
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-dark" id="cookieAccept2">{{ $tAcceptAll }}</button>
            <button type="button" class="btn btn-light border" id="cookieReject2">{{ $tRejectAll }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Legal modal (About/Terms/Privacy) -->
    <div class="modal fade" id="legalModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width:760px;">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold" id="legalModalTitle"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body pt-0" id="legalModalBody" style="color:#111827"></div>
        </div>
      </div>
    </div>

  </div>

  <!-- Bootstrap 5 (LOCAL) -->
  <script src="{{ asset('vendor/bootstrap5/bootstrap.bundle.min.js') }}"></script>

  @if($recEnabled)
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  @endif
  <script>
    (function(){
      // Show/hide password
      const toggleBtn = document.getElementById('togglePass');
      const passInput = document.getElementById('login_password');
      if(toggleBtn && passInput){
        toggleBtn.addEventListener('click', function(){
          const isPass = passInput.getAttribute('type') === 'password';
          passInput.setAttribute('type', isPass ? 'text' : 'password');
          const icon = this.querySelector('i');
          if(icon){
            icon.classList.toggle('fa-eye', !isPass);
            icon.classList.toggle('fa-eye-slash', isPass);
          }
        });
      }

      // Tooltip + detección básica por longitud (Cédula/RUC/Pasaporte)
      try {
        const iconEl = document.getElementById('usuarioHelpIcon');
        if (iconEl && window.bootstrap && bootstrap.Tooltip) {
          new bootstrap.Tooltip(iconEl);
        }

        const u = document.getElementById('login_usuario');
        const hint = document.getElementById('usuarioHint');
        function updateUsuarioHint(){
          if(!u || !hint) return;
          const raw = (u.value || '').trim();
          const alnum = raw.replace(/[^a-zA-Z0-9]/g, '');
          if (/^\d+$/.test(alnum)) {
            if (alnum.length === 10) {
              hint.textContent = 'Detectado: Cédula (10 dígitos)';
            } else if (alnum.length === 13) {
              hint.textContent = 'Detectado: RUC (13 dígitos)';
            } else {
              hint.textContent = 'Número: verifica longitud (10 cédula / 13 RUC)';
            }
          } else if (alnum.length > 0) {
            hint.textContent = 'Detectado: Pasaporte (alfanumérico)';
          } else {
            hint.textContent = 'Cédula (10) / RUC (13) / Pasaporte';
          }
        }
        if (u) {
          u.addEventListener('input', updateUsuarioHint);
          updateUsuarioHint();
        }
      } catch (e) { /* ignore */ }

      // Legal modal
      const legal = {
        about: {
          title: @json($tAboutTitle),
          html: @json(pg_t('login.about_us', $legal['about'] ?? ''))
        },
        terms: {
          title: @json($tTermsTitle),
          html: @json(pg_t('login.terms', $legal['terms'] ?? ''))
        },
        privacy: {
          title: @json($tPrivacyTitle),
          html: @json(pg_t('login.privacy', $legal['privacy'] ?? ''))
        }
      };

      const legalModalEl = document.getElementById('legalModal');
      const legalModal = legalModalEl ? new bootstrap.Modal(legalModalEl) : null;
      const legalTitle = document.getElementById('legalModalTitle');
      const legalBody = document.getElementById('legalModalBody');

      document.querySelectorAll('.js-legal').forEach(a => {
        a.addEventListener('click', function(e){
          e.preventDefault();
          const key = this.getAttribute('data-legal');
          const item = legal[key];
          if(!item || !legalModal) return;
          if(legalTitle) legalTitle.textContent = item.title || '';
          if(legalBody) legalBody.innerHTML = item.html || '';
          legalModal.show();
        });
      });

      // Cookie consent (stored as real cookie)
      const bar = document.getElementById('cookieBar');
      const choose = document.getElementById('cookieChoose');
      const modalEl = document.getElementById('cookieModal');
      const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

      function setCookie(name, value, days){
        const d = new Date();
        d.setTime(d.getTime() + (days*24*60*60*1000));
        document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
      }
      function getCookie(name){
        const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()\\[\\]\\\\\/\\+^]/g, '\\$&') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
      }

      function setConsent(val){
        setCookie('cookie_consent', val, 180);
        if(bar) bar.style.display = 'none';
      }
      function hasConsent(){
        return !!getCookie('cookie_consent');
      }
      if(bar && !hasConsent()) bar.style.display = 'flex';

      const btnA1 = document.getElementById('cookieAccept');
      const btnR1 = document.getElementById('cookieReject');
      const btnA2 = document.getElementById('cookieAccept2');
      const btnR2 = document.getElementById('cookieReject2');

      if (btnA1) btnA1.addEventListener('click', () => setConsent('all'));
      if (btnR1) btnR1.addEventListener('click', () => setConsent('reject'));
      if (btnA2) btnA2.addEventListener('click', () => { setConsent('all'); if(modal) modal.hide(); });
      if (btnR2) btnR2.addEventListener('click', () => { setConsent('reject'); if(modal) modal.hide(); });

      if (choose && modal) {
        choose.addEventListener('click', function(e){
          e.preventDefault();
          modal.show();
        });
      }
    })();
  </script>
</body>
</html>
