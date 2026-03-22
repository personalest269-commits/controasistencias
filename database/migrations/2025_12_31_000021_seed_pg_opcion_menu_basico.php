<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_permisos') || !Schema::hasTable('pg_permisos_role')) {
            return;
        }

        // Permiso para gestionar menú
        $permName = 'pg_opcion_menu';
        if (!DB::table('pg_permisos')->where('name', $permName)->exists()) {
            DB::table('pg_permisos')->insert([
                'name' => $permName,
                'display_name' => 'Administración: Menú',
                'description' => 'Gestionar opciones del menú y acceso por rol',
            ]);
        }

        $permId = DB::table('pg_permisos')->where('name', $permName)->value('id');
        if ($permId && Schema::hasTable('roles')) {
            $roleIds = array_values(array_filter([
                DB::table('roles')->where('name', 'Super-Admin')->value('id'),
                DB::table('roles')->where('name', 'Admin')->value('id'),
            ]));
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

        if (!Schema::hasTable('pg_opcion_menu') || !Schema::hasTable('pg_opcion_menu_rol')) {
            return;
        }

        // Roles por nombre
        $roleNames = ['Super-Admin', 'Admin'];
        if (Schema::hasTable('roles')) {
            $roleNamesDb = DB::table('roles')->whereIn('name', $roleNames)->pluck('name')->toArray();
            if (!empty($roleNamesDb)) {
                $roleNames = $roleNamesDb;
            }
        }

        // Menú raíz "Gestión"
        $gestionId = DB::table('pg_opcion_menu')
            ->where('titulo', 'Gestión')
            ->whereNull('id_padre')
            ->value('id');

        if (!$gestionId) {
            $gestionId = DB::table('pg_opcion_menu')->insertGetId([
                'titulo' => 'Gestión',
                'id_padre' => null,
                'url' => '#',
                'tipo' => 'G',
                'activo' => 'S',
                'orden' => 40,
                'id_archivo' => null,
                'estado' => null,
            ]);
        }

        // Menú raíz "Administración"
        $adminRootId = DB::table('pg_opcion_menu')
            ->where('titulo', 'Administración')
            ->whereNull('id_padre')
            ->value('id');

        if (!$adminRootId) {
            $adminRootId = DB::table('pg_opcion_menu')->insertGetId([
                'titulo' => 'Administración',
                'id_padre' => null,
                'url' => '#',
                'tipo' => 'G',
                'activo' => 'S',
                'orden' => 90,
                'id_archivo' => null,
                'estado' => null,
            ]);
        }

        $items = [
            // Gestión
            ['titulo' => 'Personas', 'id_padre' => $gestionId, 'url' => 'PersonasIndex', 'tipo' => 'M', 'orden' => 1],
            ['titulo' => 'Archivos digitales', 'id_padre' => $gestionId, 'url' => 'ArchivosDigitalesIndex', 'tipo' => 'M', 'orden' => 2],
            ['titulo' => 'Estado civil', 'id_padre' => $gestionId, 'url' => 'EstadoCivilIndex', 'tipo' => 'M', 'orden' => 3],
            ['titulo' => 'Tipo identificación', 'id_padre' => $gestionId, 'url' => 'TipoIdentificacionIndex', 'tipo' => 'M', 'orden' => 4],

            // Administración
            ['titulo' => 'Opciones de menú', 'id_padre' => $adminRootId, 'url' => 'OpcionMenuIndex', 'tipo' => 'M', 'orden' => 1],
        ];

        foreach ($items as $i) {
            $existsId = DB::table('pg_opcion_menu')
                ->where('titulo', $i['titulo'])
                ->where('id_padre', $i['id_padre'])
                ->value('id');

            if (!$existsId) {
                $existsId = DB::table('pg_opcion_menu')->insertGetId([
                    'titulo' => $i['titulo'],
                    'id_padre' => $i['id_padre'],
                    'url' => $i['url'],
                    'tipo' => $i['tipo'],
                    'activo' => 'S',
                    'orden' => $i['orden'],
                    'id_archivo' => null,
                    'estado' => null,
                ]);
            }

            // Asignar a roles
            foreach ($roleNames as $rname) {
                $pivotExists = DB::table('pg_opcion_menu_rol')
                    ->where('id_opcion_menu', $existsId)
                    ->where('rol', $rname)
                    ->exists();
                if (!$pivotExists) {
                    DB::table('pg_opcion_menu_rol')->insert([
                        'id_opcion_menu' => $existsId,
                        'rol' => $rname,
                        'estado' => null,
                    ]);
                }
            }
        }

        // Asignar roots a roles
        foreach ([$gestionId, $adminRootId] as $rootId) {
            foreach ($roleNames as $rname) {
                $pivotExists = DB::table('pg_opcion_menu_rol')
                    ->where('id_opcion_menu', $rootId)
                    ->where('rol', $rname)
                    ->exists();
                if (!$pivotExists) {
                    DB::table('pg_opcion_menu_rol')->insert([
                        'id_opcion_menu' => $rootId,
                        'rol' => $rname,
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
