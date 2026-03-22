<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale() ?? 'es'); ?>">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e(config('app.name')); ?></title>
  <!-- Google Font removido para mantener todo local -->
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/fontawesome-free/css/all.min.css')); ?>">
  <!-- Ionicons removido (AdminLTE ya usa FontAwesome). Si lo necesitas, agrega un CSS local en public/vendor/ionicons/ -->
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')); ?>">
  <!-- iCheck -->
  <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css')); ?>">
  <!-- JQVMap -->
  <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/jqvmap/jqvmap.min.css')); ?>">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo e(asset('admin_lte/dist/css/adminlte.min.css')); ?>">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/overlayScrollbars/css/OverlayScrollbars.min.css')); ?>">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/daterangepicker/daterangepicker.css')); ?>">
  <!-- summernote -->
  <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/summernote/summernote-bs4.min.css')); ?>">
  <!-- jQuery -->
  <script src="<?php echo asset('assets/js/jquery.min.js') ?>"></script>
  <!-- ajax form-->
  <script type="text/javascript" src="<?php echo asset('assets/js/jquery.form.min.js'); ?>"></script>
  <!-- extra resources --> 
  <link href="<?php echo asset('assets/css/icheck/flat/green.css'); ?>" rel="stylesheet" />
  <link rel="stylesheet" type="text/css" media="screen" href="<?php echo asset('assets/css/admin_lte_custom.css'); ?>" />
  <link rel="stylesheet" type="text/css" media="screen" href="<?php echo asset('assets/css/overrides.css'); ?>" />
  <script src="<?php echo e(asset('admin_lte/plugins/sweetalert2/sweetalert2.all.min.js')); ?>"></script>
   <?php $__env->startSection('head'); ?>
   <?php echo $__env->yieldSection(); ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed">

<?php
  $__authUser = Auth::user();
  $__avatarUrl = asset('photos/img.jpg');
  $__displayName = '';

  if ($__authUser) {
      $__displayName = (string)($__authUser->name ?? '');
      if (!empty($__authUser->email)) {
          $__displayName .= ' - ' . $__authUser->email;
      }

      // Prioridad: id_archivo (ad_archivo_digital) -> image (legacy) -> default
      if (!empty($__authUser->id_archivo)) {
          try {
              $__avatarUrl = route('ArchivosDigitalesVer', ['id' => $__authUser->id_archivo]);
          } catch (\Throwable $e) {
              $__avatarUrl = asset('photos/img.jpg');
          }
      } elseif (!empty($__authUser->image)) {
          if (file_exists(public_path('photos/' . $__authUser->image))) {
              $__avatarUrl = asset('photos/' . $__authUser->image);
          } else {
              $__avatarUrl = $__authUser->image;
          }
      }
  }
?>

<div class="wrapper">

   <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?php echo e(url('/admin')); ?>" class="nav-link">Home</a>
      </li>
	      <?php
	        $showFrontendLink = true;
	        try {
	          $showFrontendLink = \App\Models\PgConfiguracion::bool('UI_LINK_FRONTEND_ACTIVO', true);
	        } catch (\Throwable $e) {
	          $showFrontendLink = true;
	        }
	      ?>
      <?php if($showFrontendLink): ?>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?php echo e(url('/')); ?>" target="_blank" rel="noopener" class="nav-link">🌍 <?php echo e(tr('Frontend')); ?></a>
      </li>
      <?php endif; ?>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
	      <?php
	        $showTemplateSwitch = true;
	        try {
	          $showTemplateSwitch = \App\Models\PgConfiguracion::bool('UI_SWITCH_TEMPLATE_ACTIVO', true);
	        } catch (\Throwable $e) {
	          $showTemplateSwitch = true;
	        }
	      ?>
      <?php if($showTemplateSwitch): ?>
      <?php echo $__env->make('partials.ui_template_switcher_adminlte', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php endif; ?>
      <?php echo $__env->make('partials.lang_switcher_adminlte', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
      <li class="dropdown user user-menu open">
                      <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                        <img src="<?php echo e($__avatarUrl); ?>" class="user-image" alt="User Image">
                        <span class="hidden-xs"><?php echo e($__displayName); ?></span>
                      </a>
                      <ul class="dropdown-menu">
                        <!-- User image -->
                        <li class="user-header">
                          <img src="<?php echo e($__avatarUrl); ?>" class="img-circle" alt="User Image">
                          <p>
                            <?php echo e($__displayName); ?>

                            <small><?php echo e(config('app.name')); ?></small>
                          </p>
                        </li>
                        <!-- Menu Footer-->
                        <li class="user-footer">
                          <div class="pull-left">
                            <a href="<?php echo e(route('userprofile')); ?>" class="btn btn-default btn-flat"><?php echo e(tr('Perfil')); ?></a>
                          </div>
                          <div class="pull-right">
                            <a href="<?php echo e(route('logout')); ?>" class="btn btn-default btn-flat"><?php echo e(tr('Salir')); ?> </a>
                          </div>
                        </li>
                      </ul>
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
          <img src="<?php echo e($__avatarUrl); ?>" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="<?php echo e(route('userprofile')); ?>" class="d-block"><?php echo e($__displayName); ?></a>
        </div>
      </div>

      <!-- SidebarSearch Form (custom: filtra items generados desde pg_opcion_menu) -->
      <div class="form-inline">
        <div class="input-group">
          <input id="menuSearchInput" class="form-control form-control-sidebar" type="search" placeholder="<?php echo e(tr('Search')); ?>" aria-label="<?php echo e(tr('Search')); ?>">
          <div class="input-group-append">
            <button class="btn btn-sidebar" type="button">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>
      <div id="menuSearchNoResult" class="px-3 mt-1 text-muted" style="display:none; font-size: 12px;"><?php echo e(tr('No element found!')); ?></div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-header">MENU</li>

        <?php
            // Si el nuevo menú (pg_opcion_menu) está activo, NO mostrar ítems legacy/hardcodeados.
            // Así, si un ítem está eliminado (estado = 'X') o no asignado por rol, no aparecerá.
            $usePgMenu = (bool)($menu_pg_enabled ?? false);
        ?>

        <?php if(!$usePgMenu): ?>
        
        <li class="nav-item">
            <a href="<?php echo e(url('/admin')); ?>" class="nav-link <?php if(URL::full()==url('/')): ?> active <?php endif; ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo e(url('/admin')); ?>" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>Widgets</p>
            </a>
        </li>        

        <?php endif; ?>
        
        
        <?php echo $__env->make('templates.admin_lte.partials.sidebar_menu', ['items' => $all_menu_items, 'level' => 0], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if(!$usePgMenu): ?>

            <?php if(!empty(array_intersect(array('Invoices'), $user_permissions_names)) ): ?>
                <li class="nav-item">
                    <a href="<?php echo e(Route('InvoicesIndex')); ?>" class="nav-link <?php if(Ch::isActive('InvoicesIndex')): ?> active <?php endif; ?>">
                      <i class="nav-icon fas fa fa-chart-bar"></i>
                      <p>Invoices</p>
                    </a>
                </li>                                    
            <?php endif; ?>
            <?php if(!empty(array_intersect(array('modulebuilder_menu', 'modulebuilder_modules'), $user_permissions_names)) && Config::get('sysconfig.crudbuilder')): ?>                   
            <li class="nav-item <?php if(Ch::isActive(['modulebuildermenu','all_modules'])): ?> menu-open <?php endif; ?>" >                          
                <a href="#" class="nav-link <?php if(Ch::isActive(['modulebuildermenu','all_modules'])): ?> active <?php endif; ?>">
                <i class="nav-icon fa fa-cubes"></i>
                <p>
                      <?php echo app('translator')->get('crud_builder.menu_title'); ?>
                      <i class="right fas fa-angle-left"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                <?php if(in_array('modulebuilder_menu', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(Route('modulebuildermenu')); ?>" class="nav-link <?php if(Ch::isActive('modulebuildermenu')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('menu.menu_title'); ?></p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(in_array('modulebuilder_modules', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(Route('all_modules')); ?>" class="nav-link <?php if(Ch::isActive('all_modules')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('modules.menu_title'); ?></p>
                    </a>
                </li>
                <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <?php if(!empty(array_intersect(array('users', 'roles_all', 'permissions'), $user_permissions_names))): ?>                   
            <li class="nav-item <?php if(Ch::isActive(['users','roles','permissions'])): ?> menu-open <?php endif; ?>" >                          
                <a href="#" class="nav-link <?php if(Ch::isActive(['users','roles','permissions'])): ?> active <?php endif; ?>">
                <i class="nav-icon fa fa-users"></i>
                <p>
                      <?php echo app('translator')->get('manage_users.menu_title'); ?>
                      <i class="right fas fa-angle-left"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                <?php if(in_array('user_all', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(Route('users')); ?>" class="nav-link <?php if(Ch::isActive('users')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('users.menu_title'); ?></p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(in_array('roles_all', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(Route('roles')); ?>" class="nav-link <?php if(Ch::isActive('roles')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('roles.menu_title'); ?></p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(in_array('permissions_all', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(Route('permissions')); ?>" class="nav-link <?php if(Ch::isActive('permissions')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('permissions.menu_title'); ?></p>
                    </a>
                </li>
                <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <?php if(!empty(array_intersect(array('filemanager'), $user_permissions_names)) && Config::get('sysconfig.filemanager')): ?>                   
            <li class="nav-item" >                          
                <a href="#" class="nav-link">
                <i class="nav-icon fa fa-file"></i>
                <p>
                      <?php echo app('translator')->get('file_manager.menu_title'); ?>
                      <i class="right fas fa-angle-left"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                <?php if(in_array('filemanager', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(url('admin/laravel-filemanager')); ?>?type=Files" class="nav-link">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('file_manager.menu_title'); ?></p>
                    </a>
                </li>
                <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            <?php if(!empty(array_intersect(array('user_profile'), $user_permissions_names))): ?>
            <li class="nav-item <?php if(Ch::isActive(['userprofile','general-settings']) || URL::full()==url('admin/translations')): ?> menu-open <?php endif; ?>" >                          
                <a href="#" class="nav-link <?php if(Ch::isActive(['userprofile','general-settings'])): ?> active <?php endif; ?>">
                <i class="nav-icon fa fa-user-circle"></i>
                <p>
                <?php echo app('translator')->get('account_settings.menu_title'); ?>
                      <i class="right fas fa-angle-left"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                <?php if(in_array('user_profile', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(route('userprofile')); ?>" class="nav-link <?php if(Ch::isActive('userprofile')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('user_profile.menu_title'); ?></p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(in_array('general_settings_all', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(url('admin/general-settings')); ?>" class="nav-link <?php if(Ch::isActive('general-settings')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('general_settings.menu_title'); ?></p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo e(url('admin/login-settings')); ?>" class="nav-link <?php if(\Request::is('admin/login-settings')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Login Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo e(url('admin/email-settings')); ?>" class="nav-link">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Email Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo e(url('admin/email-templates')); ?>" class="nav-link">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Email Templates</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(in_array('translation-manager', $user_permissions_names)): ?>
                <li class="nav-item">
                    <a href="<?php echo e(url('admin/translations')); ?>" class="nav-link <?php if(\URL::full()==url('admin/translations')): ?> active <?php endif; ?>">
                      <i class="far fa-circle nav-icon"></i>
                      <p><?php echo app('translator')->get('translations.menu_title'); ?></p>
                    </a>
                </li>
                <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if(!empty(array_intersect(array('user_profile'), $user_permissions_names))): ?>
            <li class="nav-item <?php if(Ch::isActive('ApiDocumentationIndex')): ?> menu-open <?php endif; ?>" >                          
                <a href="<?php echo e(Route('ApiDocumentationIndex')); ?>" class="nav-link <?php if(Ch::isActive('ApiDocumentationIndex')): ?> active <?php endif; ?>">
                <i class="nav-icon fa fa fa-code"></i>
                <p>Api Documentation</p>
                </a>
            </li>
            <?php endif; ?>

        <?php endif; ?>
            </ul>
    </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <?php $__env->startSection('content'); ?>
    This is the master content.
    <?php echo $__env->yieldSection(); ?>
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
<!-- ./wrapper -->
<?php $__env->startSection('footer'); ?>
<?php echo $__env->yieldSection(); ?>
<!-- icheck -->
 <script src="<?php echo asset('assets/js/icheck/icheck.min.js') ?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?php echo e(asset('admin_lte/plugins/jquery-ui/jquery-ui.min.js')); ?>"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="<?php echo e(asset('admin_lte/plugins/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
<!-- ChartJS -->
<script src="<?php echo e(asset('admin_lte/plugins/chart.js/Chart.min.js')); ?>"></script>
<!-- Sparkline -->
<script src="<?php echo e(asset('admin_lte/plugins/sparklines/sparkline.js')); ?>"></script>
<!-- JQVMap -->
<script src="<?php echo e(asset('admin_lte/plugins/jqvmap/jquery.vmap.min.js')); ?>"></script>
<script src="<?php echo e(asset('admin_lte/plugins/jqvmap/maps/jquery.vmap.usa.js')); ?>"></script>
<!-- jQuery Knob Chart -->
<script src="<?php echo e(asset('admin_lte/plugins/jquery-knob/jquery.knob.min.js')); ?>"></script>
<!-- daterangepicker -->
<script src="<?php echo e(asset('admin_lte/plugins/moment/moment.min.js')); ?>"></script>
<script src="<?php echo e(asset('admin_lte/plugins/daterangepicker/daterangepicker.js')); ?>"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="<?php echo e(asset('admin_lte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')); ?>"></script>
<!-- Summernote -->
<script src="<?php echo e(asset('admin_lte/plugins/summernote/summernote-bs4.min.js')); ?>"></script>
<!-- overlayScrollbars -->
<script src="<?php echo e(asset('admin_lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')); ?>"></script>
<!-- AdminLTE App -->
<script src="<?php echo e(asset('admin_lte/dist/js/adminlte.js')); ?>"></script>
<!-- AdminLTE for demo purposes -->
<!--<script src="<?php echo e(asset('admin_lte/dist/js/demo.js')); ?>"></script> -->
<!-- PNotify -->
<script type="text/javascript" src="<?php echo e(asset('assets/js/notify/pnotify.core.js')); ?>"></script>
<script type="text/javascript" src="<?php echo e(asset('assets/js/notify/pnotify.buttons.js')); ?> "></script>
<script type="text/javascript" src="<?php echo e(asset('assets/js/notify/pnotify.nonblock.js')); ?>"></script>

<script>
  // Buscador de menú (funciona con items generados desde pg_opcion_menu y también legacy).
  (function () {
    const input = document.getElementById('menuSearchInput');
    if (!input) return;

    const noResult = document.getElementById('menuSearchNoResult');
    const sidebar = document.querySelector('aside.main-sidebar');
    const menu = sidebar ? sidebar.querySelector('ul.nav-sidebar') : null;
    if (!menu) return;

    function normalize(s) {
      return (s || '').toString().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
    }

    function applyFilter() {
      const q = normalize(input.value.trim());
      let visibleCount = 0;

      // Top level items
      const items = Array.from(menu.querySelectorAll(':scope > li.nav-item'));
      items.forEach(li => {
        const text = normalize(li.innerText);
        const parentMatch = q && text.includes(q);

        // Si hay hijos, evaluar si algún hijo matchea
        const children = Array.from(li.querySelectorAll('ul.nav-treeview > li.nav-item'));
        let childMatch = false;
        if (children.length && q && !parentMatch) {
          children.forEach(cli => {
            const ctext = normalize(cli.innerText);
            const showChild = ctext.includes(q);
            cli.style.display = showChild ? '' : 'none';
            if (showChild) childMatch = true;
          });
        } else if (children.length && q && parentMatch) {
          // Si coincide el PADRE, mostrar TODOS los hijos
          children.forEach(cli => (cli.style.display = ''));
          childMatch = true;
        } else {
          // sin query: mostrar hijos
          children.forEach(cli => (cli.style.display = ''));
        }

        const match = !q || parentMatch || childMatch;
        li.style.display = match ? '' : 'none';
        if (match) visibleCount++;

        // Mantener abierto el grupo cuando hay búsqueda
        if (children.length) {
          if (q && match) {
            li.classList.add('menu-open');
          } else if (!q) {
            // no tocar estado cuando no hay búsqueda
          }
        }
      });

      if (noResult) {
        noResult.style.display = (q && visibleCount === 0) ? '' : 'none';
      }
    }

    input.addEventListener('input', applyFilter);
  })();
</script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/templates/admin_lte/master.blade.php ENDPATH**/ ?>