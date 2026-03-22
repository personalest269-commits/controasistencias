<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asegura permisos base del sistema (usuarios/roles/permisos/settings/builder/filemanager)
 * para que no tengas que insertarlos manualmente en una instalación nueva.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_permisos') || !Schema::hasTable('pg_permisos_role') || !Schema::hasTable('roles')) {
            return;
        }

        $now = now();

        $permissions = [
            // Usuarios
            ['name' => 'user_all', 'display_name' => 'Usuarios - Ver', 'description' => 'Acceso a listado de usuarios'],
            ['name' => 'user_edit', 'display_name' => 'Usuarios - Editar', 'description' => 'Editar usuarios'],
            ['name' => 'user_create_update', 'display_name' => 'Usuarios - Crear/Actualizar', 'description' => 'Crear/Actualizar usuarios'],
            ['name' => 'user_delete', 'display_name' => 'Usuarios - Eliminar', 'description' => 'Eliminar usuarios'],
            ['name' => 'user_delete_muliple', 'display_name' => 'Usuarios - Eliminar múltiple', 'description' => 'Eliminar múltiple usuarios'],

            // Perfil
            ['name' => 'user_profile', 'display_name' => 'Perfil - Ver', 'description' => 'Ver perfil'],
            ['name' => 'user_profile_update', 'display_name' => 'Perfil - Actualizar', 'description' => 'Actualizar perfil'],

            // Roles
            ['name' => 'roles_all', 'display_name' => 'Roles - Ver', 'description' => 'Acceso a roles'],
            ['name' => 'roles_edit', 'display_name' => 'Roles - Editar', 'description' => 'Editar roles'],
            ['name' => 'roles_create_update', 'display_name' => 'Roles - Crear/Actualizar', 'description' => 'Crear/Actualizar roles'],
            ['name' => 'roles_delete', 'display_name' => 'Roles - Eliminar', 'description' => 'Eliminar roles'],
            ['name' => 'roles_delete_multiple', 'display_name' => 'Roles - Eliminar múltiple', 'description' => 'Eliminar múltiple roles'],

            // Permisos
            ['name' => 'permissions_all', 'display_name' => 'Permisos - Ver', 'description' => 'Acceso a permisos'],
            ['name' => 'permissions_edit', 'display_name' => 'Permisos - Editar', 'description' => 'Editar permisos'],
            ['name' => 'permissions_create_update', 'display_name' => 'Permisos - Crear/Actualizar', 'description' => 'Crear/Actualizar permisos'],
            ['name' => 'permissions_delete', 'display_name' => 'Permisos - Eliminar', 'description' => 'Eliminar permisos'],
            ['name' => 'permissions_delete_multiple', 'display_name' => 'Permisos - Eliminar múltiple', 'description' => 'Eliminar múltiple permisos'],

            // Settings
            ['name' => 'general_settings_all', 'display_name' => 'General Settings - Ver', 'description' => 'Acceso a configuración general'],
            ['name' => 'general_settings_create_update', 'display_name' => 'General Settings - Actualizar', 'description' => 'Actualizar configuración general'],

            // Extras
            ['name' => 'filemanager', 'display_name' => 'File Manager', 'description' => 'Acceso al file manager'],
            ['name' => 'modulebuilder_modules', 'display_name' => 'Module Builder - Modules', 'description' => 'Acceso a módulos'],
            ['name' => 'modulebuilder_menu', 'display_name' => 'Module Builder - Menu', 'description' => 'Acceso a menú del builder'],
            ['name' => 'Invoices', 'display_name' => 'Invoices', 'description' => 'Acceso a Invoices'],
            ['name' => 'Invoicedetails', 'display_name' => 'Invoice Details', 'description' => 'Acceso a Invoicedetails'],
            ['name' => 'Widgets', 'display_name' => 'Widgets', 'description' => 'Acceso a Widgets'],
        ];

        foreach ($permissions as $p) {
            $exists = DB::table('pg_permisos')->where('name', $p['name'])->exists();
            if (!$exists) {
                DB::table('pg_permisos')->insert([
                    'name' => $p['name'],
                    'display_name' => $p['display_name'],
                    'description' => $p['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                    'estado' => null,
                ]);
            }
        }

        // Asignar permisos a Super-Admin y Admin
        $roleIds = array_values(array_filter([
            DB::table('roles')->where('name', 'Super-Admin')->value('id'),
            DB::table('roles')->where('name', 'Admin')->value('id'),
        ]));

        if (empty($roleIds)) {
            return;
        }

        $permIds = DB::table('pg_permisos')
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('id')
            ->toArray();

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
                $pivotExists = DB::table('pg_permisos_role')
                    ->where('permission_id', $permId)
                    ->where('role_id', $roleId)
                    ->exists();
                if (!$pivotExists) {
                    DB::table('pg_permisos_role')->insert([
                        'permission_id' => $permId,
                        'role_id' => $roleId,
                        'estado' => null,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
