<?php
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Session;
    $currentLang = (string) Session::get('lang', app()->getLocale() ?: 'es');
    $idiomas = collect();
    try {
        if (Schema::hasTable('idiomas')) {
            $idiomas = \App\Models\Idioma::query()
                ->where('activo', 1)
                ->orderByDesc('por_defecto')
                ->orderBy('nombre')
                ->get(['codigo','nombre']);
        }
    } catch (\Throwable $e) {
        $idiomas = collect();
    }
?>

<?php if($idiomas->count() > 0): ?>
  <li class="nav-item dropdown">
    <a class="nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
      <i class="fas fa-language"></i>
      <span class="ml-1"><?php echo e($idiomas->firstWhere('codigo', $currentLang)->nombre ?? strtoupper($currentLang)); ?></span>
    </a>
    <div class="dropdown-menu dropdown-menu-right p-0">
      <?php $__currentLoopData = $idiomas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <form method="POST" action="<?php echo e(route('setLang')); ?>" class="m-0">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="lang" value="<?php echo e($lang->codigo); ?>">
          <button type="submit" class="dropdown-item <?php echo e($lang->codigo === $currentLang ? 'active' : ''); ?>">
            <?php echo e($lang->nombre); ?>

          </button>
        </form>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </li>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/partials/lang_switcher_adminlte.blade.php ENDPATH**/ ?>