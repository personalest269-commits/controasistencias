<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @if(Config::get('sysconfig.direction')=='rtl') dir="rtl" @else dir="ltr" @endif> 
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        <!-- Bootstrap core CSS -->
        <link href="<?php echo asset('assets/css/bootstrap.min.css'); ?>" rel="stylesheet">
        <link href="<?php echo asset('assets/fonts/css/font-awesome.min.css') ?>" rel="stylesheet">
        <link href="<?php echo asset('assets/css/animate.min.css'); ?>" rel="stylesheet">
        <!-- Custom styling plus plugins -->
        @if(Config::get('sysconfig.direction')=='ltr')
        <link href="<?php echo asset('assets/css/gentelella_custom.css'); ?>" rel="stylesheet">
        @elseif(Config::get('sysconfig.direction')=='rtl')
        <link href="<?php echo asset('assets/css/custom-rtl2.css'); ?>" rel="stylesheet">
        @endif
        <link href="<?php echo asset('assets/css/icheck/flat/green.css'); ?>" rel="stylesheet" />
        <link href="<?php echo asset('assets/css/overrides.css'); ?>" rel="stylesheet" />
        <link href="<?php echo asset('assets/css/floatexamples.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo asset('assets/css/jquery-ui.theme.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo asset('assets/css/jquery-ui.min.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo asset('assets/css/datetimepicker/bootstrap-datetimepicker.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo asset('assets/css/app-builder.css'); ?>" rel="stylesheet" type="text/css" />
        <script src="<?php echo asset('assets/js/jquery.min.js') ?>"></script>
        <script src="<?php echo asset('assets/js/jquery-ui.min.js') ?>"></script>
        <script src="{{ asset('assets/js/ckeditor/ckeditor.js') }}"></script>
        <script src="<?php echo asset('assets/js/ckeditor/adapters/jquery.js') ?>"></script>
        <script src="<?php echo asset('assets/js/jquery-ui.min.js') ?>"></script>
        <script type="text/javascript" src="<?php echo asset('assets/js/moment.min.js'); ?>"></script>
        <script type="text/javascript" src="<?php echo asset('assets/js/jquery.form.min.js'); ?>"></script>
        {{-- Bootstrap DateTimePicker (LOCAL) --}}
        <script type="text/javascript" src="{{ asset('vendor/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js') }}"></script>
        <script type="text/javascript" src="<?php echo asset('assets/js/datetimepicker/collapse.js') ?>"></script>
        <script src="<?php echo asset('assets/js/progressbar/bootstrap-progressbar.min.js'); ?>"></script>
        <script src="{{ asset('admin_lte/plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
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
            }

            if ($__authUser && !empty($__authUser->id_archivo)) {
                // Foto desde ad_archivo_digital (BD cifrada)
                try {
                    $__avatarUrl = route('ArchivosDigitalesVer', ['id' => $__authUser->id_archivo]);
                } catch (\Throwable $e) {
                    $__avatarUrl = asset('photos/img.jpg');
                }
            } elseif ($__authUser && !empty($__authUser->image)) {
                // Fallback legacy: archivo físico en /public/photos o URL directa
                if (file_exists(public_path('photos/' . $__authUser->image))) {
                    $__avatarUrl = asset('photos/' . $__authUser->image);
                } else {
                    $__avatarUrl = $__authUser->image;
                }
            }
        @endphp
        <div class="container body">
            <div class="main_container">
                <div class="col-md-3 left_col">
                    <div class="left_col scroll-view">                    
                        <div class="clearfix"></div>
                        <!-- menu prile quick info -->
                        <div class="profile">
                            <div class="profile_pic">
                                <img src="{{ $__avatarUrl }}" alt="..." class="img-circle profile_img">
                            </div>
                            <div class="profile_info">
                                <span>Welcome,</span>
                                <h2 style='width: 150px'>{{  $__displayName }}</h2>
                            </div>
                        </div>
                        <!-- /menu prile quick info -->
                        <br />
                        <!-- sidebar menu -->
                        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                            <div class="menu_section">
                                <h3>General</h3>
                                <div style="padding: 0 10px 10px 10px;">
                                    <input id="menuSearchInput" type="text" class="form-control" placeholder="Search" style="height: 34px;">
                                    <div id="menuSearchNoResult" class="text-muted" style="display:none; font-size: 12px; margin-top: 5px;">No element found!</div>
                                </div>
                                <ul class="nav side-menu">
	                                    @php
	                                        /**
	                                         * Build href for dynamic menu items.
	                                         *
	                                         * In some installations the menu table stores the route **name** (e.g. "PgTraduccionesIndex"),
	                                         * and in others it stores a URL/path (e.g. "/importaciones-logs").
	                                         *
	                                         * Gentelella used route() for type=module, which fails when the value is a path.
	                                         */
	                                        if (!function_exists('pg_menu_href')) {
	                                            function pg_menu_href($u) {
	                                                $u = $u ?? '#';
	                                                $name = ltrim($u, '/');
	                                                return ($name !== '' && \Illuminate\Support\Facades\Route::has($name))
	                                                    ? route($name)
	                                                    : url($u);
	                                            }
	                                        }
	                                    @endphp
                                    @if(empty($menu_pg_enabled))
                                    <li>
                                        <a href="{{  Route('dashboardIndex') }}">
                                            <i class="fa fa-dashboard"></i> DashBoard
                                        </a>
                                        <ul class="nav child_menu" style="display: none;"></ul>
                                    </li>
                                    @endif
                                    @forelse($all_menu_items as $menu_item)
                                    <?php //if (!empty(array_intersect(array('users','roles','permissions'), $user_permissions_names))): ?>
	                                    <li>
	                                        <a href="@if($menu_item['type']=='module'){!! pg_menu_href($menu_item['url']) !!} @else {{ url($menu_item['url']) }} @endif"><i class="fa {{ $menu_item['icon'] }}"></i> {{ tr($menu_item['name']) }} 
                                            @if(isset($menu_item['children']) && !empty($menu_item['children']))
                                            <span class="fa fa-chevron-down"></span>
                                            @endif
                                        </a>
                                        <ul class="nav child_menu" style="display: none">
	                                            @forelse($menu_item['children'] as $menu_item_children)
	                                            <li><a href="@if($menu_item_children['type']=='module') {!! pg_menu_href($menu_item_children['url']) !!} @else {{ url($menu_item_children['url']) }} @endif"> {{ tr($menu_item_children['name']) }}</a></li>
                                            @empty
                                            @endforelse
                                        </ul>
                                    </li>
                                    @empty
                                    @endforelse

                                    {{--
                                        Menú legacy (Entrust + table menus). Se oculta cuando está activo el nuevo
                                        menú (pg_opcion_menu + pg_opcion_menu_rol) para evitar duplicados.
                                    --}}
                                    @if(empty($menu_pg_enabled))
                                    @if(!empty(array_intersect(array('Invoices'), $user_permissions_names)) )
                                    <li>
                                        <a href="{{  Route('InvoicesIndex') }}">
                                            <i class="fa fa-area-chart"></i> Invoices
                                        </a>
                                        <ul class="nav child_menu" style="display: none;"></ul>
                                    </li>
                                    @endif
                                    @if(!empty(array_intersect(array('modulebuilder_menu', 'modulebuilder_modules'), $user_permissions_names)) && Config::get('sysconfig.crudbuilder'))
                                    <li><a><i class="fa fa-cubes"></i>@lang('crud_builder.menu_title')<span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu" style="display: none">
                                            @if(in_array('modulebuilder_menu', $user_permissions_names))
                                            <li><a href="{{ Route('modulebuildermenu') }}">@lang('menu.menu_title')</a></li>
                                            @endif
                                            @if (in_array('modulebuilder_modules', $user_permissions_names))
                                            <li><a href="{{ Route('all_modules') }}">@lang('modules.menu_title')</a></li>
                                            @endif
                                        </ul>
                                    </li>
                                    @endif
                                    @if(!empty(array_intersect(array('users', 'roles_all', 'permissions'), $user_permissions_names)))
                                    <li><a><i class="fa fa-users"></i> @lang('manage_users.menu_title') <span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu" style="display: none">
                                            @if(in_array('user_all', $user_permissions_names))
                                            <li><a href="<?php echo Route('users'); ?>">@lang('users.menu_title')</a></li>
                                            @endif
                                            @if(in_array('roles_all', $user_permissions_names))
                                            <li><a href="{{ Route('roles') }}">@lang('roles.menu_title')</a></li>
                                            @endif
                                            @if (in_array('permissions_all', $user_permissions_names))
                                            <li><a href="<?php echo Route('permissions'); ?>">@lang('permissions.menu_title')</a></li>
                                            @endif
                                        </ul>
                                    </li>
                                    @endif
                                    @if(!empty(array_intersect(array('filemanager'), $user_permissions_names)) && Config::get('sysconfig.filemanager'))
                                    <li><a><i class="fa fa-file-o"></i> @lang('file_manager.menu_title') <span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu" style="display: none">
                                            @if (in_array('filemanager', $user_permissions_names))
                                            <li><a href="{{ url('admin/laravel-filemanager') }}?type=Files">@lang('file_manager.menu_title')</a></li>
                                            @endif
                                        </ul>
                                    </li>
                                    @endif
                                    @if(!empty(array_intersect(array('user_profile'), $user_permissions_names)))
                                    <li><a><i class="fa fa-user-circle"></i> @lang('account_settings.menu_title') <span class="fa fa-chevron-down"></span></a>
                                        <ul class="nav child_menu" style="display: none">
                                            @if (in_array('user_profile', $user_permissions_names))
                                            <li><a href="{{ Route('userprofile') }}">@lang('user_profile.menu_title')</a></li>
                                            @endif
                                            @if (in_array('general_settings_all', $user_permissions_names))
                                            <li><a href="{{ Route('general-settings') }}">@lang('general_settings.menu_title')</a></li>
                                            <li><a href="{{ url('admin/login-settings') }}">Login Settings</a></li>
                                            <li><a href="{{ Route('email-settings') }}">Email Settings</a></li>
                                            <li><a href="{{ Route('email-templates') }}">Email Templates</a></li>
                                            @endif
                                            @if (in_array('translation-manager', $user_permissions_names))
                                            <li><a href="{{ url('admin/translations') }}">@lang('translations.menu_title')</a></li>
                                            @endif
                                        </ul>
                                    </li>
                                    @endif
                                    <li>
                                        <a href="{{  Route('ApiDocumentationIndex') }}">
                                            <i class="fa fa-code"></i> Api Documentation
                                        </a>
                                        <ul class="nav child_menu" style="display: none;"></ul>
                                    </li>
                                    @endif
                                </ul>
                            </div>

                        </div>
                        <!-- /sidebar menu -->

                        <!-- /menu footer buttons -->
                        <div class="sidebar-footer hidden-small">
                            <a  data-toggle="tooltip" data-placement="top" title="Settings" href="{{ route('general-settings') }}">
                                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
                            </a>
                            <a class="fullscreen" data-toggle="tooltip" data-placement="top" title="FullScreen" onclick="openFullscreen()">
                                <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
                            </a>
                            <a class="fullscreen-exit" data-toggle="tooltip" data-placement="top" title="FullScreenExit" onclick="closeFullscreen()" style="display: none">
                                <span class="glyphicon glyphicon-resize-small" aria-hidden="true"></span>
                            </a>
                            <a data-toggle="tooltip" data-placement="top" title="Lock">
                                <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
                            </a>
                            <a data-toggle="tooltip" data-placement="top" title="Logout" href="{{ Route('logout') }}">
                                <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
                            </a>
                        </div>
                        <!-- /menu footer buttons -->
                    </div>
                </div>

                <!-- top navigation -->
                <div class="top_nav">

                    <div class="nav_menu">
                        <nav class="" role="navigation">
                            <div class="nav toggle">
                                <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                            </div>

                            <ul class="nav navbar-nav navbar-right">
								@php
								  $showFrontendLink = true;
								  try {
								    $showFrontendLink = \App\Models\PgConfiguracion::bool('UI_LINK_FRONTEND_ACTIVO', true);
								  } catch (\Throwable $e) {
								    $showFrontendLink = true;
								  }
								@endphp
                                @if($showFrontendLink)
                                <li>
                                    <a href="{{ url('/') }}" target="_blank" rel="noopener">
                                        <i class="fa fa-globe"></i> {{ tr('Frontend') }}
                                    </a>
                                </li>
                                @endif
								@php
								  $showTemplateSwitch = true;
								  try {
								    $showTemplateSwitch = \App\Models\PgConfiguracion::bool('UI_SWITCH_TEMPLATE_ACTIVO', true);
								  } catch (\Throwable $e) {
								    $showTemplateSwitch = true;
								  }
								@endphp
                                @if($showTemplateSwitch)
                                @include('partials.ui_template_switcher_gentelella')
                                @endif
                                @include('partials.lang_switcher_gentelella')
                                <li class="">
                                    <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <img src="{{ $__avatarUrl }}" alt="">{{ $__displayName ?: ($__authUser->name ?? session('name')) }}
                                        <span class=" fa fa-angle-down"></span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-usermenu animated fadeInDown pull-right">
                                        @if (in_array('user-profile-view', $user_permissions_names))
                                        <li><a href="{{ route('userprofile') }}">  Profile</a></li>
                                        @endif
                                        <li><a href="{{ Route('logout') }}"><i class="fa fa-sign-out pull-right"></i> Log Out</a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </nav>
                    </div>

                </div>
                <!-- /top navigation -->


                <!-- page content -->
                <div class="right_col" role="main">
                    @section('content')
                    This is the master content.
                    @show
                </div>
                <!-- /page content -->
            </div>

        </div>
        <div id="custom_notifications" class="custom-notifications dsp_none">
            <ul class="list-unstyled notifications clearfix" data-tabbed_notifications="notif-group">
            </ul>
            <div class="clearfix"></div>
            <div id="notif-group" class="tabbed_notifications"></div>
        </div>
        <script src="<?php echo asset('assets/js/bootstrap.min.js') ?>"></script>
        <!-- icheck -->
        <script src="<?php echo asset('assets/js/icheck/icheck.min.js') ?>"></script>

        <script src="<?php echo asset('assets/js/custom.js'); ?>"></script>
        <link rel="stylesheet" href="{{ asset('admin_lte/plugins/fontawesome-free/css/all.min.css') }}">

        <!-- /footer content -->
        <script type="text/javascript">
$('textarea.editor').ckeditor();
        </script>
        <div class="ajaxLoader" style="display: none;width: 100%;background-color: white;position: fixed;z-index: 1000;height: 100%;top: 0px;opacity: 0.7;">
            <div class="progress progress-striped progress_wide" style="width: 40%;margin: 0 auto;top: 50%;">
                <div class="progress-bar progress-bar-success" data-transitiongoal="10" aria-valuenow="10" style="width: 10%;"></div>
            </div>
        </div>
        <!-- PNotify -->
        <script type="text/javascript" src="{{ asset('assets/js/notify/pnotify.core.js')}}"></script>
        <script type="text/javascript" src="{{ asset('assets/js/notify/pnotify.buttons.js')}} "></script>
        <script type="text/javascript" src="{{ asset('assets/js/notify/pnotify.nonblock.js')}}"></script>

        <script>
            // Buscador de menú para Gentelella (items generados desde pg_opcion_menu).
            (function () {
                var input = document.getElementById('menuSearchInput');
                var noResult = document.getElementById('menuSearchNoResult');
                var menu = document.querySelector('#sidebar-menu ul.nav.side-menu');
                if (!input || !menu) return;

                function normalize(s) {
                    return (s || '').toString().toLowerCase().replace(/\s+/g, ' ').trim();
                }

                function applyFilter() {
                    var q = normalize(input.value);
                    var items = Array.prototype.slice.call(menu.children);
                    var visibleCount = 0;

                    items.forEach(function (li) {
                        if (!li || li.tagName !== 'LI') return;

                        // Texto del parent (solo del <a> principal)
                        var a = li.querySelector(':scope > a');
                        var text = normalize(a ? a.innerText : li.innerText);

                        var childMenu = li.querySelector(':scope > ul.child_menu');
                        var childLis = childMenu ? Array.prototype.slice.call(childMenu.querySelectorAll(':scope > li')) : [];

                        var childMatch = false;
                        var parentMatch = q && (text.indexOf(q) !== -1);
                        if (q && childLis.length && !parentMatch) {
                            childLis.forEach(function (cli) {
                                var ctext = normalize(cli.innerText);
                                var showChild = ctext.indexOf(q) !== -1;
                                cli.style.display = showChild ? '' : 'none';
                                if (showChild) childMatch = true;
                            });
                        } else if (q && childLis.length && parentMatch) {
                            // Si coincide el PADRE, mostrar TODOS los hijos
                            childLis.forEach(function (cli) { cli.style.display = ''; });
                            childMatch = true;
                        } else if (!q && childLis.length) {
                            childLis.forEach(function (cli) { cli.style.display = ''; });
                        }

                        var match = (!q) || parentMatch || childMatch;
                        li.style.display = match ? '' : 'none';
                        if (match) visibleCount++;

                        // Si hay búsqueda y coincide, abrir el grupo.
                        if (childMenu) {
                            if (q && match) {
                                childMenu.style.display = 'block';
                            } else if (!q) {
                                // no forzar cuando no hay búsqueda
                            } else {
                                // búsqueda activa pero no coincide
                                childMenu.style.display = 'none';
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
        @section('footer')
        @show
    </body>

</html>
