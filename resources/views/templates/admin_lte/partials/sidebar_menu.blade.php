@php
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
@endphp

@foreach(($items ?? []) as $it)
    @php
        $children = $it['children'] ?? [];
        $hasChildren = is_array($children) && count($children) > 0;

        $open = $hasChildren && $isActive($it);
        $activeSelfOnly = !$hasChildren && $isActive($it);

        $href = $hasChildren ? '#' : $resolveHref($it);
    @endphp

    <li class="nav-item {{ $open ? 'menu-open' : '' }}">
        <a href="{{ $href }}" class="nav-link {{ ($open || $activeSelfOnly) ? 'active' : '' }}">
            <i class="{{ $resolveIconClass($it, $level) }}"></i>
            <p>
                {{ tr($it['name'] ?? '') }}
                @if($hasChildren)
                    <i class="right fas fa-angle-left"></i>
                @endif
            </p>
        </a>

        @if($hasChildren)
            <ul class="nav nav-treeview">
                @include('templates.admin_lte.partials.sidebar_menu', ['items' => $children, 'level' => $level + 1])
            </ul>
        @endif
    </li>
@endforeach
