<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permisos para configuración del frontend.
 */
return new class extends Migration
{
    private function nextId(string $table): string
    {
        $max = (string) (DB::table($table)->max('id') ?? '');

        // IDs son numéricos con padding 0 (ej: 0000000001). Si viniera vacío, iniciar en 1.
        $n = 0;
        if ($max !== '') {
            // limpiar cualquier caracter no numérico por seguridad
            $digits = preg_replace('/\D+/', '', $max) ?? '';
            $n = $digits === '' ? 0 : (int) ltrim($digits, '0');
        }

        $n = $n + 1;
        return str_pad((string) $n, 10, '0', STR_PAD_LEFT);
    }

    public function up(): void
    {
        if (!Schema::hasTable('pg_permisos') || !Schema::hasTable('pg_permisos_role') || !Schema::hasTable('roles')) {
            return;
        }

        $now = now();

        $permissions = [
            ['name' => 'fr_fronted_all', 'display_name' => 'Frontend - Ver', 'description' => 'Acceso a configuración del frontend'],
            ['name' => 'fr_fronted_update', 'display_name' => 'Frontend - Actualizar', 'description' => 'Actualizar configuración del frontend'],
        ];

        foreach ($permissions as $p) {
            $exists = DB::table('pg_permisos')->where('name', $p['name'])->exists();
            if (!$exists) {
                $data = [
                    'id' => $this->nextId('pg_permisos'),
                    'name' => $p['name'],
                    'display_name' => $p['display_name'],
                    'description' => $p['description'],
                ];
                if (Schema::hasColumn('pg_permisos', 'created_at')) {
                    $data['created_at'] = $now;
                }
                if (Schema::hasColumn('pg_permisos', 'updated_at')) {
                    $data['updated_at'] = $now;
                }
                if (Schema::hasColumn('pg_permisos', 'estado')) {
                    $data['estado'] = null;
                }

                DB::table('pg_permisos')->insert($data);
            }
        }

        // Asignar a Super-Admin y Admin
        $roleIds = array_values(array_filter([
            DB::table('roles')->where('name', 'Super-Admin')->value('id'),
            DB::table('roles')->where('name', 'Admin')->value('id'),
        ]));

        if (empty($roleIds)) {
            return;
        }

        $permIds = DB::table('pg_permisos')->whereIn('name', array_column($pg_permisos, 'name'))
            ->pluck('id')->toArray();

        foreach ($roleIds as $roleId) {
            foreach ($permIds as $permId) {
                $pivotExists = DB::table('pg_permisos_role')
                    ->where('permission_id', $permId)
                    ->where('role_id', $roleId)
                    ->exists();
                if (!$pivotExists) {
                    $data = [
                        'permission_id' => $permId,
                        'role_id' => $roleId,
                    ];
                    if (Schema::hasColumn('pg_permisos_role', 'estado')) {
                        $data['estado'] = null;
                    }
                    DB::table('pg_permisos_role')->insert($data);
                }
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
