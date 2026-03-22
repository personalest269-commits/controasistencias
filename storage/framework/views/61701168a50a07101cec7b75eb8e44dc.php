<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale() ?? 'es'); ?>">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title><?php echo e(config('app.name')); ?></title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="<?php echo e(asset('frontEndTemplates/creative/assets/img/favicon.ico')); ?>" />
        <!-- Font Awesome (LOCAL) -->
        <link rel="stylesheet" href="<?php echo e(asset('admin_lte/plugins/fontawesome-free/css/all.min.css')); ?>" />
        <!-- Google Fonts removidos para mantener todo local -->
        <!-- Third party plugin CSS (LOCAL) -->
        <link rel="stylesheet" href="<?php echo e(asset('vendor/magnific-popup/magnific-popup.min.css')); ?>" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="<?php echo e(asset('frontEndTemplates/creative/css/styles.css')); ?>" rel="stylesheet" />
    </head>
    <body id="page-top">
        <?php
            // Asegurar variable $fr (parametrización frontend)
            if (!isset($fr)) {
                try { $fr = \App\Services\FrFrontedService::get(); } catch (\Throwable $e) { $fr = []; }
            }
            $pagina = $fr['pagina'] ?? null;
            $siteName = $pagina ? ($pagina->t('nombre_sitio') ?: config('app.name')) : config('app.name');
            $logoUri = null;
            if ($pagina && !empty($pagina->logo_archivo_id)) {
                $logoUri = \App\Services\ArchivoDigitalDataUriService::imageDataUri($pagina->logo_archivo_id);
            }
        ?>
        <!-- Navigation-->
        <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
            <div class="container">
                <a class="navbar-brand js-scroll-trigger" href="<?php echo e(url('/')); ?>">
                    <?php if($logoUri): ?>
                        <img src="<?php echo e($logoUri); ?>" alt="logo" style="max-height:34px;max-width:180px;object-fit:contain;">
                    <?php else: ?>
                        <?php echo e($siteName); ?>

                    <?php endif; ?>
                </a>
                <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ml-auto my-2 my-lg-0">
                        <?php $items = $fr['menu'] ?? []; ?>
                        <?php if(!empty($items) && count($items) > 0): ?>
                            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $href = '#';
                                    if ($m->tipo === 'anchor') {
                                        $href = $m->destino ?: '#';
                                    } elseif ($m->tipo === 'route') {
                                        if (!empty($m->destino) && \Illuminate\Support\Facades\Route::has($m->destino)) {
                                            $href = route($m->destino);
                                        } else {
                                            $href = url($m->destino ?: '/');
                                        }
                                    } else {
                                        $href = $m->destino ?: '#';
                                    }
                                    $target = ($m->nuevo_tab ?? 'N') === 'S' ? '_blank' : null;
                                ?>
                                <li class="nav-item">
                                    <a class="nav-link js-scroll-trigger" href="<?php echo e($href); ?>" <?php if($target): ?> target="<?php echo e($target); ?>" rel="noopener" <?php endif; ?>>
                                        <?php echo e($m->t()); ?>

                                    </a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#about"><?php echo e(tr('Acerca de')); ?></a></li>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#services"><?php echo e(tr('Servicios')); ?></a></li>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#portfolio"><?php echo e(tr('Portafolio')); ?></a></li>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="<?php echo e(route('blogs')); ?>"><?php echo e(tr('Blog')); ?></a></li>
                            <li class="nav-item"><a class="nav-link js-scroll-trigger" href="#contact"><?php echo e(tr('Contacto')); ?></a></li>
                        <?php endif; ?>

                        
                        <?php
                            $currentLang = app()->getLocale() ?: (session('lang') ?: 'es');
                            $idiomasList = isset($idiomas) ? $idiomas : collect();
                        ?>
                        <?php if($idiomasList && count($idiomasList) > 0): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    🌐 <?php echo e(strtoupper($currentLang)); ?>

                                </a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="langDropdown">
                                    <?php $__currentLoopData = $idiomasList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idioma): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php $code = strtolower((string)($idioma->codigo ?? 'es')); ?>
                                        <a class="dropdown-item" href="<?php echo e(route('lang.change', $code)); ?>">
                                            <?php echo e($idioma->nombre); ?>

                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
        <?php $__env->startSection('content'); ?>
        <?php echo $__env->yieldSection(); ?>
        <!-- Footer-->
        <footer class="bg-light py-5">
            <div class="container"><div class="small text-center text-muted"></div></div>
        </footer>
        <!-- Bootstrap core JS-->
        <script src="<?php echo e(asset('admin_lte/plugins/jquery/jquery.min.js')); ?>"></script>
        <script src="<?php echo e(asset('admin_lte/plugins/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
        <!-- Third party plugin JS-->
        <script src="<?php echo e(asset('vendor/jquery-easing/jquery.easing.min.js')); ?>"></script>
        <script src="<?php echo e(asset('vendor/magnific-popup/jquery.magnific-popup.min.js')); ?>"></script>
        <!-- Core theme JS-->
        <script src="<?php echo e(asset('frontEndTemplates/creative/js/scripts.js')); ?>"></script>
    </body>
</html><?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/frontEndTemplates/creative/master.blade.php ENDPATH**/ ?>