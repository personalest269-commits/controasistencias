<?php $__env->startSection('content'); ?>
<?php
    $pagina = $fr['pagina'] ?? null;
    $sec = $fr['seccion'] ?? [];
    $about = $sec['about'] ?? null;
    $servicesSec = $sec['services'] ?? null;
    $portfolioSec = $sec['portfolio'] ?? null;
    $cta = $sec['cta'] ?? null;
    $contactSec = $sec['contact'] ?? null;

    $heroBg = null;
    if ($pagina && !empty($pagina->hero_fondo_archivo_id)) {
        $heroBg = \App\Services\ArchivoDigitalDataUriService::imageDataUri($pagina->hero_fondo_archivo_id);
    }

    $heroTitle = $pagina ? ($pagina->t('hero_titulo') ?: tr('Control de Asistencia Municipal')) : tr('Control de Asistencia Municipal');
    $heroSubtitle = $pagina ? ($pagina->t('hero_subtitulo') ?: '') : '';
    $heroBtnText = $pagina ? ($pagina->t('hero_boton_texto') ?: tr('Ingresar')) : tr('Ingresar');
 $heroBtnUrl = $pagina && !empty($pagina->hero_boton_url)
    ? url($pagina->hero_boton_url)
    : route('login');
    // Normalizar: si por error configuraron rutas del login público, forzar al login del admin
    $hb = '/' . ltrim((string)$heroBtnUrl, '/');
    if (in_array($hb, ['/login', '/auth/login', '/admin'], true)) {
        $heroBtnUrl = '/admin/login';
    }

    $servicios = $fr['servicios'] ?? collect();
    $portafolio = $fr['portafolio'] ?? collect();
?>

<!-- Masthead-->
<header class="masthead" <?php if($heroBg): ?> style="background-image:url('<?php echo e($heroBg); ?>');" <?php endif; ?>>
    <div class="container h-100">
        <div class="row h-100 align-items-center justify-content-center text-center">
            <div class="col-lg-10 align-self-end">
                <h1 class="text-uppercase text-white font-weight-bold"><?php echo e($heroTitle); ?></h1>
                <hr class="divider my-4" />
            </div>
            <div class="col-lg-8 align-self-baseline">
                <?php if(!empty($heroSubtitle)): ?>
                    <p class="text-white-75 font-weight-light mb-5"><?php echo nl2br(e($heroSubtitle)); ?></p>
                <?php endif; ?>
                <a class="btn btn-primary btn-xl js-scroll-trigger" href="<?php echo e($heroBtnUrl); ?>"><?php echo e($heroBtnText); ?></a>
            </div>
        </div>
    </div>
</header>

<!-- About-->
<?php if($about && ($about->mostrar ?? 'S') === 'S'): ?>
<section class="page-section <?php echo e($about->clase_css ?: 'bg-primary'); ?>" id="about">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="text-white mt-0"><?php echo e($about->t('titulo') ?: tr('Acerca del sistema')); ?></h2>
                <hr class="divider light my-4" />
                <p class="text-white-50 mb-4"><?php echo nl2br(e($about->t('contenido') ?: tr('Control de asistencia, justificaciones, eventos y reportes.'))); ?></p>
                <?php if(!empty($about->boton_url)): ?>
                    <a class="btn btn-light btn-xl js-scroll-trigger" href="<?php echo e($about->boton_url); ?>"><?php echo e($about->t('boton_texto') ?: tr('Ver más')); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Services-->
<?php if($servicesSec && ($servicesSec->mostrar ?? 'S') === 'S'): ?>
<section class="page-section" id="services">
    <div class="container">
        <h2 class="text-center mt-0"><?php echo e($servicesSec->t('titulo') ?: tr('Servicios')); ?></h2>
        <hr class="divider my-4" />
        <?php if(!empty($servicesSec->t('contenido'))): ?>
            <p class="text-center text-muted mb-5"><?php echo nl2br(e($servicesSec->t('contenido'))); ?></p>
        <?php endif; ?>
        <div class="row">
            <?php $srvList = $servicios instanceof \Illuminate\Support\Collection ? $servicios : collect($servicios); ?>
            <?php $__empty_1 = true; $__currentLoopData = $srvList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $srv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="col-lg-3 col-md-6 text-center">
                    <div class="mt-5">
                        <i class="fas fa-4x <?php echo e($srv->icono ?: 'fa-check'); ?> text-primary mb-4"></i>
                        <h3 class="h4 mb-2"><?php echo e($srv->t('titulo')); ?></h3>
                        <p class="text-muted mb-0"><?php echo nl2br(e($srv->t('descripcion'))); ?></p>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-12 text-center text-muted"><?php echo e(tr('No hay servicios configurados.')); ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Portfolio-->
<?php if($portfolioSec && ($portfolioSec->mostrar ?? 'S') === 'S'): ?>
<section id="portfolio">
    <div class="container-fluid p-0">
        <div class="row no-gutters">
            <?php
                $portList = $portafolio instanceof \Illuminate\Support\Collection ? $portafolio : collect($portafolio);
                $i = 0;
            ?>
            <?php $__empty_1 = true; $__currentLoopData = $portList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $i++;
                    $img = null;
                    if (!empty($p->imagen_archivo_id)) {
                        $img = \App\Services\ArchivoDigitalDataUriService::imageDataUri($p->imagen_archivo_id);
                    }
                    if (!$img) {
                        $img = asset('frontEndTemplates/creative/assets/img/portfolio/fullsize/'.(($i-1)%6+1).'.jpg');
                    }
                    $href = !empty($p->url) ? $p->url : $img;
                ?>
                <div class="col-lg-4 col-sm-6">
                    <a class="portfolio-box" href="<?php echo e($href); ?>" <?php if(!empty($p->url)): ?> target="_blank" rel="noopener" <?php endif; ?>>
                        <img class="img-fluid" src="<?php echo e($img); ?>" alt="" />
                        <div class="portfolio-box-caption">
                            <div class="project-category text-white-50"><?php echo e($p->t('categoria')); ?></div>
                            <div class="project-name"><?php echo e($p->t('titulo')); ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-12 text-center text-muted p-5"><?php echo e(tr('No hay portafolio configurado.')); ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to action-->
<?php if($cta && ($cta->mostrar ?? 'S') === 'S'): ?>
<section class="page-section <?php echo e($cta->clase_css ?: 'bg-dark text-white'); ?>">
    <div class="container text-center">
        <h2 class="mb-4"><?php echo e($cta->t('titulo') ?: tr('¿Listo para empezar?')); ?></h2>
        <?php if(!empty($cta->t('contenido'))): ?>
            <p class="mb-4"><?php echo nl2br(e($cta->t('contenido'))); ?></p>
        <?php endif; ?>
        <?php if(!empty($cta->boton_url)): ?>
              <a class="btn btn-light btn-xl" href="<?php echo e($heroBtnUrl); ?>"><?php echo e($cta->t('hero_boton_texto') ?: tr('Ingresar')); ?></a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- Contact-->
<?php if($contactSec && ($contactSec->mostrar ?? 'S') === 'S'): ?>
<section class="page-section" id="contact">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="mt-0"><?php echo e($contactSec->t('titulo') ?: tr('Contacto')); ?></h2>
                <hr class="divider my-4" />
                <?php if(!empty($contactSec->t('contenido'))): ?>
                    <p class="text-muted mb-5"><?php echo nl2br(e($contactSec->t('contenido'))); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 ml-auto text-center mb-5 mb-lg-0">
                <i class="fas fa-phone fa-3x mb-3 text-muted"></i>
                <div><?php echo e($pagina->contacto_telefono ?? ''); ?></div>
            </div>
            <div class="col-lg-4 mr-auto text-center">
                <i class="fas fa-envelope fa-3x mb-3 text-muted"></i>
                <?php if(!empty($pagina->contacto_email)): ?>
                    <a class="d-block" href="mailto:<?php echo e($pagina->contacto_email); ?>"><?php echo e($pagina->contacto_email); ?></a>
                <?php endif; ?>
                <?php if(!empty($pagina)): ?>
                    <div class="text-muted mt-2"><?php echo e($pagina->t('contacto_direccion')); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>


<?php if($pagina && ($pagina->cookies_activo ?? 'N') === 'S'): ?>
    <div id="frCookieBanner" style="position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;display:none;">
        <div style="max-width:980px;margin:0 auto;background:#fff;border:1px solid rgba(0,0,0,.12);border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.12);padding:14px 16px;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div style="font-size:14px;line-height:1.35;">
                        <?php echo nl2br(e($pagina->t('cookies_texto') ?: tr('Este sitio usa cookies para mejorar la experiencia.'))); ?>

                    </div>
                </div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    <button class="btn btn-sm btn-primary" type="button" onclick="frCookieSet('accepted')"><?php echo e($pagina->t('cookies_btn_aceptar') ?: tr('Aceptar')); ?></button>
                    <button class="btn btn-sm btn-light" type="button" onclick="frCookieSet('rejected')"><?php echo e($pagina->t('cookies_btn_rechazar') ?: tr('Rechazar')); ?></button>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function(){
            try {
                var k = 'fr_cookie_choice';
                var v = localStorage.getItem(k);
                if (!v) {
                    var el = document.getElementById('frCookieBanner');
                    if (el) el.style.display = 'block';
                }
            } catch (e) {}
        })();
        function frCookieSet(val){
            try { localStorage.setItem('fr_cookie_choice', val); } catch (e) {}
            var el = document.getElementById('frCookieBanner');
            if (el) el.style.display = 'none';
        }
    </script>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontEndTemplates.creative.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/frontEndTemplates/creative/home.blade.php ENDPATH**/ ?>