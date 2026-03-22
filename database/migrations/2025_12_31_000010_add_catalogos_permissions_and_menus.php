<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Asegurar roles base (evita fallas por FK cuando la tabla roles está vacía)
        // Entrust crea la tabla, pero no siempre se ejecutan seeders.
        $rolesBase = [
            [
                'name' => 'Super-Admin',
                'display_name' => 'Super Admin',
                'description' => 'Super Admin',
            ],
            [
                'name' => 'Admin',
                'display_name' => 'Admin Role',
                'description' => 'This is Admin Role',
            ],
        ];

        foreach ($rolesBase as $r) {
            $exists = DB::table('roles')->where('name', $r['name'])->exists();
            if (!$exists) {
                $r['created_at'] = now();
                $r['updated_at'] = now();
                DB::table('roles')->insert($r);
            }
        }

        // Permisos
        $perms = [
            [
                'name' => 'pg_estado_civil',
                'display_name' => 'Catálogo: Estado civil',
                'description' => 'Gestionar catálogo de estado civil'
            ],
            [
                'name' => 'pg_tipo_identificacion',
                'display_name' => 'Catálogo: Tipo identificación',
                'description' => 'Gestionar catálogo de tipo de identificación'
            ],
        ];

        foreach ($perms as $p) {
            $exists = DB::table('pg_permisos')->where('name', $p['name'])->exists();
            if (!$exists) {
                DB::table('pg_permisos')->insert($p);
            }
        }

        // Asignar a roles Super-Admin y Admin (por name, no por id fijo)
        $roleIds = array_values(array_filter([
            DB::table('roles')->where('name', 'Super-Admin')->value('id'),
            DB::table('roles')->where('name', 'Admin')->value('id'),
        ]));
        foreach (['pg_estado_civil', 'pg_tipo_identificacion'] as $permName) {
            $permId = DB::table('pg_permisos')->where('name', $permName)->value('id');
            if ($permId) {
                foreach ($roleIds as $roleId) {
                    $exists = DB::table('pg_permisos_role')
                        ->where('permission_id', $permId)
                        ->where('role_id', $roleId)
                        ->exists();
                    if (!$exists) {
                        DB::table('pg_permisos_role')->insert([
                            'permission_id' => $permId,
                            'role_id' => $roleId,
                        ]);
                    }
                }
            }
        }

        // Menú (solo se muestra si el usuario tiene el permiso)
        $menus = [
            [
                'name' => 'Estado civil',
                'permission_name' => 'pg_estado_civil',
                'url' => 'EstadoCivilIndex',
                'icon' => 'fa-heart',
                'type' => 'module',
                'parent' => 0,
                'hierarchy' => 50,
                'module_id' => 0,
            ],
            [
                'name' => 'Tipo identificación',
                'permission_name' => 'pg_tipo_identificacion',
                'url' => 'TipoIdentificacionIndex',
                'icon' => 'fa-id-card',
                'type' => 'module',
                'parent' => 0,
                'hierarchy' => 51,
                'module_id' => 0,
            ],
        ];

        foreach ($menus as $m) {
            $exists = DB::table('menus')
                ->where('permission_name', $m['permission_name'])
                ->where('url', $m['url'])
                ->exists();
            if (!$exists) {
                $m['created_at'] = now();
                $m['updated_at'] = now();
                // estado NULL => activo (por trait EstadoSoftDeletes)
                if (!array_key_exists('estado', $m)) {
                    $m['estado'] = null;
                }
                DB::table('menus')->insert($m);
            }
        }
    }

    public function down(): void
    {
        // No se eliminan por seguridad (pueden estar personalizadas en producción)
    }
};
