<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pg_opcion_menu')) {
            return;
        }

        $tituloCol = Schema::hasColumn('pg_opcion_menu', 'titulo')
            ? 'titulo'
            : (Schema::hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);

        if (!$tituloCol) {
            return;
        }

        $parent = DB::table('pg_opcion_menu')
            ->whereNull('id_padre')
            ->where($tituloCol, 'Administración')
            ->first();

        if (!$parent) {
            return;
        }

        $exists = DB::table('pg_opcion_menu')
            ->where('id_padre', (string) $parent->id)
            ->where($tituloCol, 'Logs de errores API')
            ->exists();

        if ($exists) {
            return;
        }

        $data = [
            'id_padre' => (string) $parent->id,
            $tituloCol => 'Logs de errores API',
            'url' => 'PgLogsErrors',
        ];

        if (Schema::hasColumn('pg_opcion_menu', 'id')) {
            $data['id'] = $this->nextId('pg_opcion_menu');
        }
        if (Schema::hasColumn('pg_opcion_menu', 'tipo')) {
            $data['tipo'] = 'M';
        }
        if (Schema::hasColumn('pg_opcion_menu', 'activo')) {
            $data['activo'] = 'S';
        }
        if (Schema::hasColumn('pg_opcion_menu', 'orden')) {
            $data['orden'] = 98;
        }
        if (Schema::hasColumn('pg_opcion_menu', 'estado')) {
            $data['estado'] = null;
        }
        if (Schema::hasColumn('pg_opcion_menu', 'created_at')) {
            $data['created_at'] = now();
        }
        if (Schema::hasColumn('pg_opcion_menu', 'updated_at')) {
            $data['updated_at'] = now();
        }

        DB::table('pg_opcion_menu')->insert($data);

        if (Schema::hasTable('pg_opcion_menu_rol') && Schema::hasTable('roles')) {
            $menuId = isset($data['id']) ? (string) $data['id'] : (string) DB::table('pg_opcion_menu')
                ->where($tituloCol, 'Logs de errores API')
                ->where('url', 'PgLogsErrors')
                ->value('id');

            $roles = DB::table('roles')->select('id')->get();
            foreach ($roles as $role) {
                $existsPivot = DB::table('pg_opcion_menu_rol')
                    ->where('id_opcion_menu', $menuId)
                    ->where('id_rol', (string) $role->id)
                    ->exists();

                if ($existsPivot) {
                    continue;
                }

                $pivot = [
                    'id_opcion_menu' => $menuId,
                    'id_rol' => (string) $role->id,
                ];

                if (Schema::hasColumn('pg_opcion_menu_rol', 'id')) {
                    $pivot['id'] = $this->nextId('pg_opcion_menu_rol');
                }
                if (Schema::hasColumn('pg_opcion_menu_rol', 'estado')) {
                    $pivot['estado'] = null;
                }
                if (Schema::hasColumn('pg_opcion_menu_rol', 'created_at')) {
                    $pivot['created_at'] = now();
                }
                if (Schema::hasColumn('pg_opcion_menu_rol', 'updated_at')) {
                    $pivot['updated_at'] = now();
                }

                DB::table('pg_opcion_menu_rol')->insert($pivot);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pg_opcion_menu')) {
            return;
        }

        $tituloCol = Schema::hasColumn('pg_opcion_menu', 'titulo')
            ? 'titulo'
            : (Schema::hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);

        if (!$tituloCol) {
            return;
        }

        DB::table('pg_opcion_menu')
            ->where($tituloCol, 'Logs de errores API')
            ->where('url', 'PgLogsErrors')
            ->delete();
    }

    private function nextId(string $table, string $column = 'id'): string
    {
        $max = DB::table($table)->max($column);

        if (is_numeric($max)) {
            return (string) (((int) $max) + 1);
        }

        return '1';
    }
};
