<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega permiso para administrar la configuración de API (Importación de Personas)
 * y lo asigna por defecto a Super-Admin y Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_permisos') || !Schema::hasTable('pg_permisos_role') || !Schema::hasTable('roles')) {
            return;
        }

        $now = now();

        $perm = [
            'name' => 'api_config_personas_import',
            'display_name' => 'Importación Personas - Configurar API',
            'description' => 'Permite configurar valores por defecto y autenticación de la API usada en Importación de Personas',
        ];

        if (!DB::table('pg_permisos')->where('name', $perm['name'])->exists()) {
            DB::table('pg_permisos')->insert([
                'name' => $perm['name'],
                'display_name' => $perm['display_name'],
                'description' => $perm['description'],
                'created_at' => $now,
                'updated_at' => $now,
                'estado' => null,
            ]);
        }

        $permId = DB::table('pg_permisos')->where('name', $perm['name'])->value('id');
        if (!$permId) {
            return;
        }

        $roleIds = array_values(array_filter([
            DB::table('roles')->where('name', 'Super-Admin')->value('id'),
            DB::table('roles')->where('name', 'Admin')->value('id'),
        ]));

        foreach ($roleIds as $roleId) {
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

    public function down(): void
    {
        // No-op (permiso queda en catálogo)
    }
};
