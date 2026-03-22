<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Auth;
use View;
use App\Models\Menus;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        // El menú y permisos ya se comparten globalmente en el middleware InitialData.
        // Evitar sobreescribirlos aquí (esto causaba que no se vean los hijos de Gestión/Administración).
        // En Laravel 10+, View::shared() requiere al menos 1 argumento. Para obtener
        // todas las variables compartidas se debe usar getShared().
        $shared = View::getShared();
        if (is_array($shared) && (array_key_exists('all_menu_items', $shared) || array_key_exists('user_permissions_names', $shared))) {
            return;
        }
        /**
         * El menú y permisos se comparten globalmente vía middleware InitialData.
         * Evitamos sobre-escribir variables compartidas desde aquí.
         */
        try {
            $shared = View::getShared();
            if (isset($shared['all_menu_items']) && isset($shared['user_permissions_names'])) {
                return;
            }

            // Fallback legacy (si por alguna razón no se ejecutó el middleware)
            if (Auth::user()) {
                $UserPermissionsNames = [];
                $UserRoles = Auth::user()->roles;

                foreach ($UserRoles as $role) {
                    foreach ($role->permissions as $permission) {
                        $UserPermissionsNames[] = $permission->name;
                    }
                }

                $AllMenuItems = Menus::orderBy('parent', 'asc')
                    ->whereIn('permission_name', $UserPermissionsNames)
                    ->orWhere('type', 'menuItem')
                    ->orderBy('hierarchy', 'asc')
                    ->get();

                $AllMenuItemsArray = $this->GetParentChildrenMenu($AllMenuItems);

                View::share('all_menu_items', $AllMenuItemsArray);
                View::share('user_permissions_names', $UserPermissionsNames);
            }
        } catch (\Throwable $e) {
            // silent
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
