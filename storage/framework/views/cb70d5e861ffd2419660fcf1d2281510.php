<?php
    /**
     * Recursive sidebar menu renderer for AdminLTE.
     *
     * Expected variables:
     * - $items: array
     * - $level: int (optional)
     */
    $level = $level ?? 0;

    // Helper: resolve href from a menu item.
    $resolveHref = function(array $it): string {
        $isModule = ($it['type'] ?? '') === 'module';
        $u = $it['url'] ?? '#';
        if (!$u || $u === '#') return '#';
        if ($isModule) {
            return \Illuminate\Support\Facades\Route::has($u) ? route($u) : '#';
        }
        return url($u);
    };

    // Recursive active checker (self or any descendant).
    $isActive = function(array $it) use (&$isActive): bool {
        $type = (string)($it['type'] ?? '');
        $url  = (string)($it['url'] ?? '');
        $children = $it['children'] ?? [];

        $self = false;
        if ($url !== '' && $url !== '#') {
            if ($type === 'module' && \Request::route()) {
                $self = \Request::route()->getName() === $url;
            } elseif ($type !== 'module') {
                $self = \Request::is(ltrim($url, '/')) || \Request::is(ltrim($url, '/') . '/*');
            }
        }

        if ($self) return true;

        if (is_array($children)) {
            foreach ($children as $c) {
                if ($isActive($c)) return true;
            }
        }
        return false;
    };

    // Icon resolver (supports either "fa-xxx" or full "fas fa-xxx" classes).
    $resolveIconClass = function(array $it, int $lvl): string {
        $raw = trim((string)($it['icon'] ?? ''));
        if ($lvl > 0) {
            // Sub-items look like the reference screenshot (small circle bullet)
            return 'far fa-circle nav-icon';
        }
        if ($raw === '') return 'nav-icon fas fa-circle';
        if (str_contains($raw, 'fa ') || str_contains($raw, 'fas') || str_contains($raw, 'far') || str_contains($raw, 'fab') || str_starts_with($raw, 'fa-')) {
            // If DB stores full FA classes, respect it.
            return 'nav-icon ' . $raw;
        }
        // Backward compatibility: store just the suffix (e.g. "users")
        return 'nav-icon fas fa-' . $raw;
    };
?>

<?php $__currentLoopData = ($items ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $children = $it['children'] ?? [];
        $hasChildren = is_array($children) && count($children) > 0;

        $open = $hasChildren && $isActive($it);
        $activeSelfOnly = !$hasChildren && $isActive($it);

        $href = $hasChildren ? '#' : $resolveHref($it);
    ?>

    <li class="nav-item <?php echo e($open ? 'menu-open' : ''); ?>">
        <a href="<?php echo e($href); ?>" class="nav-link <?php echo e(($open || $activeSelfOnly) ? 'active' : ''); ?>">
            <i class="<?php echo e($resolveIconClass($it, $level)); ?>"></i>
            <p>
                <?php echo e(tr($it['name'] ?? '')); ?>

                <?php if($hasChildren): ?>
                    <i class="right fas fa-angle-left"></i>
                <?php endif; ?>
            </p>
        </a>

        <?php if($hasChildren): ?>
            <ul class="nav nav-treeview">
                <?php echo $__env->make('templates.admin_lte.partials.sidebar_menu', ['items' => $children, 'level' => $level + 1], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </ul>
        <?php endif; ?>
    </li>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\xampp\htdocs\laravelfinal89\resources\views/templates/admin_lte/partials/sidebar_menu.blade.php ENDPATH**/ ?>