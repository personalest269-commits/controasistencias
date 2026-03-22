<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\Menus;
use App\Models\PgOpcionMenu;
use App\Models\PgOpcionMenuRol;
use View;
use App\Models\Settings;
use Schema;
use Session;

// Nota: el título y breadcrumb de página se calculan desde el menú (pg_opcion_menu)
// para que cambien con el idioma (tr()) sin editar cada formulario.

class InitialData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // Flag para que las vistas oculten el menú legacy cuando el nuevo menú (pg_opcion_menu)
            // está activo. Evita duplicados como Dashboard/Widgets/Manage Users.
            View::share('menu_pg_enabled', false);

            if (Schema::hasTable('Settings')) {
                $Settings = Settings::where('id', 1)->first();
                View::share('settings', $Settings);
            }

            if (Schema::hasTable('pg_usuario') && Auth::user()) {
                // UI template por usuario: se toma de pg_usuario.id_plantillas -> pg_plantillas.codigo
                // y se guarda en sesión para que las vistas seleccionen el layout correcto.
                try {
                    $ui = 'gentelella';
                    if (Schema::hasColumn('pg_usuario', 'id_plantillas') && Schema::hasTable('pg_plantillas')) {
                        $u = Auth::user();
                        // usar relación si existe, si no fallback a query
                        if (method_exists($u, 'plantilla') && $u->plantilla && !empty($u->plantilla->codigo)) {
                            $ui = (string) $u->plantilla->codigo;
                        } else {
                            $pid = $u->id_plantillas ?? null;
                            if ($pid) {
                                $code = \DB::table('pg_plantillas')->where('id', $pid)->value('codigo');
                                if ($code) {
                                    $ui = (string) $code;
                                }
                            }
                        }
                    }
                    Session::put('ui_template', $ui);

                    // IMPORTANT:
                    // Muchas vistas existentes aún dependen de config('sysconfig.theme') / Config::get('sysconfig.theme')
                    // para decidir el layout (por ejemplo: @extends("templates.".config("sysconfig.theme").".master")).
                    // Para que el cambio de plantilla (Gentelella/AdminLTE) funcione en TODOS los formularios
                    // sin tener que editar cada vista, sincronizamos el valor de configuración en runtime.
                    config(['sysconfig.theme' => $ui]);
                } catch (\Throwable $e) {
                    // ignore
                }

                $UserPermissionsNames = [];
                $UserRoles = Auth::user()->roles;

                foreach ($UserRoles as $role) {
                    foreach ($role->permissions as $permission) {
                        $UserPermissionsNames[] = $permission->name;
                    }
                }

                // Get Menu Items (nuevo menú por rol, fallback al menú anterior)
                if (
                    Schema::hasTable('pg_opcion_menu')
                    && Schema::hasTable('pg_opcion_menu_rol')
                    && Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')
                ) {
                    View::share('menu_pg_enabled', true);
                    $AllMenuItemsArray = $this->GetParentChildrenMenuPg();
                } else {
                    $AllMenuItems = Menus::orderBy('parent', 'asc')
                        ->whereIn('permission_name', $UserPermissionsNames)
                        ->orWhere('type', 'menuItem')
                        ->orderBy('hierarchy', 'asc')
                        ->get();
                    $AllMenuItemsArray = $this->GetParentChildrenMenu($AllMenuItems);
                }

                View::share('all_menu_items', $AllMenuItemsArray);
                View::share('user_permissions_names', $UserPermissionsNames);

                // Título/Breadcrumb global basado en el menú actual
                [$pageTitle, $breadcrumb] = $this->resolvePageTitleAndBreadcrumb($request, $AllMenuItemsArray);
                View::share('pg_page_title', $pageTitle);
                View::share('pg_breadcrumb', $breadcrumb);
            }
        } catch (\Throwable $e) {
            // Evitar romper la app por fallos de menú.
            View::share('all_menu_items', []);
            View::share('user_permissions_names', []);
            View::share('pg_page_title', null);
            View::share('pg_breadcrumb', []);
        }

        return $next($request);
    }

    /**
     * Detecta el ítem activo (padre/hijo) en base a la ruta actual o path,
     * y devuelve: [titulo, breadcrumb[]].
     *
     * - $titulo: string|null (texto ES; en vista se debe pasar por tr())
     * - $breadcrumb: array de strings (texto ES)
     */
    protected function resolvePageTitleAndBreadcrumb($request, array $menuTree): array
    {
        try {
            $routeName = $request->route() ? (string) $request->route()->getName() : '';
            $path = ltrim((string) $request->path(), '/');

            foreach ($menuTree as $parent) {
                $pName = (string)($parent['name'] ?? '');
                $pType = (string)($parent['type'] ?? '');
                $pUrl  = (string)($parent['url'] ?? '');

                // Match padre
                if ($this->menuMatches($pType, $pUrl, $routeName, $path)) {
                    return [$pName, $pName !== '' ? [$pName] : []];
                }

                // Match hijos
                $children = $parent['children'] ?? [];
                if (is_array($children)) {
                    foreach ($children as $child) {
                        $cName = (string)($child['name'] ?? '');
                        $cType = (string)($child['type'] ?? '');
                        $cUrl  = (string)($child['url'] ?? '');

                        if ($this->menuMatches($cType, $cUrl, $routeName, $path)) {
                            $bc = [];
                            if ($pName !== '') $bc[] = $pName;
                            if ($cName !== '') $bc[] = $cName;
                            return [$cName !== '' ? $cName : $pName, $bc];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [null, []];
    }

    protected function menuMatches(string $type, string $url, string $routeName, string $path): bool
    {
        if ($url === '' || $url === '#') return false;

        if ($type === 'module') {
            return $routeName !== '' && $routeName === $url;
        }

        // menuItem: comparar por path
        $u = ltrim($url, '/');
        if ($u === '') return false;

        // match exacto o prefijo
        return $path === $u || str_starts_with($path, $u . '/');
    }

    /**
     * Nuevo menú: pg_opcion_menu + pg_opcion_menu_rol (por rol).
     * Construye árbol padre/hijo y convierte keys a formato compatible con las vistas.
     */
    protected function GetParentChildrenMenuPg(): array
    {
        try {
            $roleIds = [];
            if (Auth::user()) {
                $roleIds = Auth::user()->roles->pluck('id')->map(fn ($v) => (int) $v)->toArray();
            }

            // ids permitidos por rol
            $q = PgOpcionMenuRol::query()->whereNull('estado');

            // A partir de ahora el menú se controla por id_rol.
            // Si la columna no existe, no devolver nada (fallback al menú anterior ya se maneja en handle()).
            if (!Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')) {
                return [];
            }

            if (!empty($roleIds)) {
                $q->whereIn('id_rol', $roleIds);
            }

            $allowedIds = $q->pluck('id_opcion_menu')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->toArray();

            $menus = PgOpcionMenu::query()
                ->where('activo', 'S')
                ->orderBy('orden', 'asc')
                ->get();

            $byId = [];
            foreach ($menus as $m) {
                $byId[(int) $m->id] = $m;
            }

            // Incluir padres (para que aparezcan los grupos aunque no estén asignados explícitamente)
            $finalAllowed = array_fill_keys($allowedIds, true);
            foreach ($allowedIds as $id) {
                $cur = $byId[$id] ?? null;
                $guard = 0;
                while ($cur && $guard < 50) {
                    $guard++;
                    $pid = (int) ($cur->id_padre ?? 0);
                    if ($pid > 0) {
                        $finalAllowed[$pid] = true;
                        $cur = $byId[$pid] ?? null;
                        continue;
                    }
                    break;
                }
            }

            // Construir estructura (N niveles) usando un diccionario id -> item
            $items = [];
            foreach ($menus as $m) {
                $id = (int) $m->id;
                if (!isset($finalAllowed[$id])) continue;

                $items[$id] = [
                    'id' => $id,
                    'name' => $m->titulo,
                    'permission_name' => null,
                    'url' => $m->url ?? '#',
                    // Si luego agregas columna de icono en pg_opcion_menu, aquí puedes mapearla.
                    'icon' => 'fa-circle-o',
                    'id_archivo' => $m->id_archivo,
                    'type' => ($m->tipo === 'M') ? 'module' : 'menuItem',
                    'parent' => (int) ($m->id_padre ?? 0),
                    'hierarchy' => (int) ($m->orden ?? 0),
                    'children' => [],
                ];
            }

            // Enlazar hijos a padres (por referencia)
            foreach ($items as $id => &$it) {
                $pid = (int)($it['parent'] ?? 0);
                if ($pid > 0 && isset($items[$pid])) {
                    $items[$pid]['children'][] = &$it;
                }
            }
            unset($it);

            // Obtener roots
            $roots = [];
            foreach ($items as $id => $_) {
                $pid = (int)($items[$id]['parent'] ?? 0);
                if ($pid <= 0 || !isset($items[$pid])) {
                    $roots[$id] = &$items[$id];
                }
            }

            // Ordenar recursivamente por hierarchy
            $sortTree = function (&$nodes) use (&$sortTree) {
                if (!is_array($nodes)) return;
                uasort($nodes, function ($a, $b) {
                    return ($a['hierarchy'] ?? 0) <=> ($b['hierarchy'] ?? 0);
                });
                foreach ($nodes as &$n) {
                    if (!empty($n['children']) && is_array($n['children'])) {
                        $sortTree($n['children']);
                    }
                }
                unset($n);
            };

            $sortTree($roots);
            return $roots;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function GetParentChildrenMenu($AllMenuItems)
    {
        $FinalMenuItems = [];
        foreach ($AllMenuItems as $MenuItem) {
            if ($MenuItem->parent == 0) {
                $FinalMenuItems[$MenuItem->id] = $MenuItem->toArray();
                $FinalMenuItems[$MenuItem->id]['children'] = [];
            } else {
                $FinalMenuItems[$MenuItem->parent]['children'][] = $MenuItem->toArray();
            }
        }
        return $FinalMenuItems;
    }
}
