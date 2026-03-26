<!DOCTYPE html>
<html lang="en" @if(Config::get('sysconfig.direction')=='rtl') dir="rtl" @else dir="ltr" @endif>
    <head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name') }}</title>
  <!-- Google Font: Source Sans Pro -->
  {{-- Google Font removido para mantener todo local --}}
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fontawesome-free/css/all.min.css') }}">
  <!-- Ionicons -->
  {{-- Ionicons removido (usa FontAwesome). Si lo necesitas, agrega un CSS local en public/vendor/ionicons/ --}}
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
  <!-- iCheck -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
  <!-- JQVMap -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/jqvmap/jqvmap.min.css') }}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('admin_lte/dist/css/adminlte.min.css') }}">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/daterangepicker/daterangepicker.css') }}">
  <!-- summernote -->
  <link rel="stylesheet" href="{{ asset('admin_lte/plugins/summernote/summernote-bs4.min.css') }}">
  <!-- jQuery -->
  <script src="{{ asset('admin_lte/plugins/jquery/jquery.min.js') }}"></script>
  <!-- ajax form-->
  <script type="text/javascript" src="<?php echo asset('assets/js/jquery.form.min.js'); ?>"></script>
  <!-- extra resources --> 
  <link href="<?php echo asset('assets/css/icheck/flat/green.css'); ?>" rel="stylesheet" />
  <link rel="stylesheet" type="text/css" media="screen" href="<?php echo asset('assets/css/admin_lte_custom.css'); ?>" />
   @section('head')
   @show
</head>
    <body class="nav-md">

@php
  $__authUser = Auth::user();
  $__avatarUrl = asset('photos/img.jpg');
  $__displayName = '';

  if ($__authUser) {
      $__displayName = (string)($__authUser->name ?? '');
      if (!empty($__authUser->email)) {
          $__displayName .= ' - ' . $__authUser->email;
      }

      if (!empty($__authUser->id_archivo)) {
          try {
              $__avatarUrl = route('ArchivosDigitalesVer', ['id' => $__authUser->id_archivo]);
          } catch (\Throwable $e) {
              $__avatarUrl = asset('photos/img.jpg');
          }
      } elseif (!empty($__authUser->image)) {
          $__imagePath = ltrim((string)$__authUser->image, '/');
          $__photoPath = str_starts_with($__imagePath, 'photos/') ? $__imagePath : 'photos/' . $__imagePath;

          if (file_exists(public_path($__photoPath))) {
              $__avatarUrl = asset($__photoPath);
          } elseif (preg_match('#^https?://#i', (string)$__authUser->image)) {
              $__avatarUrl = $__authUser->image;
          } else {
              $__avatarUrl = asset($__imagePath);
          }
      }
  }
@endphp

        <div class="wrapper">

   <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="{{ url('/') }}" class="nav-link">Home</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      @include('partials.lang_switcher_adminlte')
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
          <i class="fas fa-th-large"></i>
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->


  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="{{ $__avatarUrl }}" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="{{ route('userprofile') }}" class="d-block">{{ $__displayName }}</a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-header">MENU</li>

        @php
            // Si el nuevo menú (pg_opcion_menu) está activo, NO mostrar ítems legacy/hardcodeados.
            // Así, si un ítem está eliminado (estado = 'X') o no asignado por rol, no aparecerá.
            $usePgMenu = (bool)($menu_pg_enabled ?? false);
        @endphp

        @if(!$usePgMenu)
        
        <li class="nav-item">
            <a href="{{ url('/')}}" class="nav-link ">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('/') }}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>Widgets</p>
            </a>
        </li>        

        @endif
        
        
        @include('templates.admin_lte.partials.sidebar_menu', ['items' => $all_menu_items, 'level' => 0])

        @if(!$usePgMenu)

            @if(!empty(array_intersect(array('Invoices'), $user_permissions_names)) )
                <li class="nav-item">
                    <a href="{{  Route('InvoicesIndex') }}" class="nav-link @if(Ch::isActive('InvoicesIndex')) active @endif">
                      <i class="nav-icon fas fa fa-chart-bar"></i>
                      <p>Invoices</p>
                    </a>
                </li>                                    
            @endif
            @if(!empty(array_intersect(array('modulebuilder_menu', 'modulebuilder_modules'), $user_permissions_names)) && Config::get('sysconfig.crudbuilder'))                   
            <li class="nav-item @if(Ch::isActive(['modulebuildermenu','all_modules'])) menu-open @endif" >                          
                <a href="#" class="nav-link @if(Ch::isActive(['modulebuildermenu','all_modules'])) active @endif">
                <i class="nav-icon fa fa-cubes"></i>
                <p>
                      @lang('crud_builder.menu_title')
                      <i class="right fas fa-angle-left"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                @if(in_array('modulebuilder_menu', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ Route('modulebuildermenu') }}" class="nav-link @if(Ch::isActive('modulebuildermenu')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('menu.menu_title')</p>
                    </a>
                </li>
                @endif
                @if (in_array('modulebuilder_modules', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ Route('all_modules') }}" class="nav-link @if(Ch::isActive('all_modules')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('modules.menu_title')</p>
                    </a>
                </li>
                @endif
                </ul>
            </li>
            @endif
            @if(!empty(array_intersect(array('users', 'roles_all', 'permissions'), $user_permissions_names)))                   
            <li class="nav-item @if(Ch::isActive(['users','roles','permissions'])) menu-open @endif" >                          
                <a href="#" class="nav-link @if(Ch::isActive(['users','roles','permissions'])) active @endif">
                <i class="nav-icon fa fa-users"></i>
                <p>
                      @lang('manage_users.menu_title')
                      <i class="right fas fa-angle-left"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                @if(in_array('user_all', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ Route('users') }}" class="nav-link @if(Ch::isActive('users')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('users.menu_title')</p>
                    </a>
                </li>
                @endif
                @if(in_array('roles_all', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ Route('roles') }}" class="nav-link @if(Ch::isActive('roles')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('roles.menu_title')</p>
                    </a>
                </li>
                @endif
                @if (in_array('permissions_all', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ Route('permissions') }}" class="nav-link @if(Ch::isActive('permissions')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('permissions.menu_title')</p>
                    </a>
                </li>
                @endif
                </ul>
            </li>
            @endif
            @if(!empty(array_intersect(array('filemanager'), $user_permissions_names)) && Config::get('sysconfig.filemanager'))                   
            <li class="nav-item @if(\URL::full()== url('admin/laravel-filemanager?type=Files') ) menu-open @endif" >                          
                <a href="#" class="nav-link @if(\URL::full()== url('admin/laravel-filemanager?type=Files')) active @endif">
                <i class="nav-icon fa fa-file"></i>
                <p>
                      @lang('file_manager.menu_title')
                      <i class="right fas fa-angle-left"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                @if (in_array('filemanager', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ url('admin/laravel-filemanager') }}?type=Files" class="nav-link @if(\URL::full()== url('admin/laravel-filemanager?type=Files')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('file_manager.menu_title')</p>
                    </a>
                </li>
                @endif
                </ul>
            </li>
            @endif
            @if(!empty(array_intersect(array('user_profile'), $user_permissions_names)))
            <li class="nav-item @if(Ch::isActive(['userprofile','general-settings'])) menu-open @endif" >                          
                <a href="#" class="nav-link @if(Ch::isActive(['userprofile','general-settings'])) active @endif">
                <i class="nav-icon fa fa-user-circle"></i>
                <p>
                @lang('account_settings.menu_title')
                      <i class="right fas fa-angle-left"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                @if (in_array('user_profile', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ route('userprofile') }}" class="nav-link @if(Ch::isActive('userprofile')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('user_profile.menu_title')</p>
                    </a>
                </li>
                @endif
                @if (in_array('general_settings_all', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ url('admin/general-settings') }}" class="nav-link @if(Ch::isActive('admin/general-settings')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('general_settings.menu_title')</p>
                    </a>
                </li>
	                <li class="nav-item">
	                    <a href="{{ url('admin/email-settings') }}" class="nav-link @if(Ch::isActive('admin/email-settings')) active @endif">
	                      <i class="far fa-circle nav-icon"></i>
	                      <p>Email Settings</p>
	                    </a>
	                </li>
	                <li class="nav-item">
	                    <a href="{{ url('admin/email-templates') }}" class="nav-link @if(Ch::isActive('admin/email-templates')) active @endif">
	                      <i class="far fa-circle nav-icon"></i>
	                      <p>Email Templates</p>
	                    </a>
	                </li>
                @endif
                @if (in_array('translation-manager', $user_permissions_names))
                <li class="nav-item">
                    <a href="{{ url('admin/translations') }}" class="nav-link @if(\URL::full()==url('admin/translations')) active @endif">
                      <i class="far fa-circle nav-icon"></i>
                      <p>@lang('translations.menu_title')</p>
                    </a>
                </li>
                @endif
                </ul>
            </li>
            @endif
            
            @if(!empty(array_intersect(array('user_profile'), $user_permissions_names)))
            <li class="nav-item @if(Ch::isActive('ApiDocumentationIndex')) menu-open @endif" >                          
                <a href="{{  Route('ApiDocumentationIndex') }}" class="nav-link @if(Ch::isActive('ApiDocumentationIndex')) active @endif">
                <i class="nav-icon fa fa fa-code"></i>
                <p>Api Documentation</p>
                </a>
            </li>
            @endif

        @endif
            </ul>
    </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    @section('content')
    This is the master content.
    @show
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
        @section('footer')
@show
<!-- icheck -->
 <script src="<?php echo asset('assets/js/icheck/icheck.min.js') ?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('admin_lte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{ asset('admin_lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- ChartJS -->
<script src="{{ asset('admin_lte/plugins/chart.js/Chart.min.js') }}"></script>
<!-- Sparkline -->
<script src="{{ asset('admin_lte/plugins/sparklines/sparkline.js') }}"></script>
<!-- JQVMap -->
<script src="{{ asset('admin_lte/plugins/jqvmap/jquery.vmap.min.js') }}"></script>
<script src="{{ asset('admin_lte/plugins/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
<!-- jQuery Knob Chart -->
<script src="{{ asset('admin_lte/plugins/jquery-knob/jquery.knob.min.js') }}"></script>
<!-- daterangepicker -->
<script src="{{ asset('admin_lte/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('admin_lte/plugins/daterangepicker/daterangepicker.js') }}"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="{{ asset('admin_lte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
<!-- Summernote -->
<script src="{{ asset('admin_lte/plugins/summernote/summernote-bs4.min.js') }}"></script>
<!-- overlayScrollbars -->
<script src="{{ asset('admin_lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('admin_lte/dist/js/adminlte.js') }}"></script>
<!-- AdminLTE for demo purposes -->
<!--<script src="{{ asset('admin_lte/dist/js/demo.js') }}"></script> -->
    </body>

</html>
