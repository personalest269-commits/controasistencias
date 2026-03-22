<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    private function nextId(string $table): string
    {
        $row = DB::selectOne("
            SELECT LPAD(IFNULL(MAX(CAST(id AS UNSIGNED)), 0) + 1, 10, '0') AS id
            FROM {$table}
        ");
        return (string) ($row->id ?? '0000000001');
    }

    public function up(): void
    {
        // pg_opcion_menu_rol: id (varchar10 PK), id_opcion_menu, id_rol, estado
        // Roles: tabla roles (spatie) -> columnas típicas: id, name, guard_name, created_at, updated_at

        $roleIds = DB::table('roles')
            ->whereIn('name', ['ADMIN', 'SUPER_ADMIN'])
            ->pluck('id')
            ->filter()
            ->values();

        if ($roleIds->isEmpty()) {
            return;
        }

        $menuIds = DB::table('pg_opcion_menu')
            ->whereIn('titulo', [
                'Gestión de Importaciones',
                'Historial de Importaciones',
                'Gestión Log',
            ])
            ->pluck('id')
            ->filter()
            ->values();

        if ($menuIds->isEmpty()) {
            return;
        }

        foreach ($menuIds as $menuId) {
            foreach ($roleIds as $roleId) {
                $exists = DB::table('pg_opcion_menu_rol')
                    ->where('id_opcion_menu', $menuId)
                    ->where('id_rol', $roleId)
                    ->first();

                if (!$exists) {
                    DB::table('pg_opcion_menu_rol')->insert([
                        'id' => $this->nextId('pg_opcion_menu_rol'),
                        'id_opcion_menu' => $menuId,
                        'id_rol' => $roleId,
                        'estado' => 'S',
                    ]);
                } else {
                    DB::table('pg_opcion_menu_rol')
                        ->where('id', $exists->id)
                        ->update(['estado' => 'S']);
                }
            }
        }
    }

    public function down(): void
    {
        // no-op por seguridad
    }
};
