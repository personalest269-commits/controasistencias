<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_opcion_menu')) {
            return;
        }

        $titleCol = Schema::hasColumn('pg_opcion_menu', 'titulo') ? 'titulo' : (Schema::hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);
        if (!$titleCol) {
            return;
        }

        $gestion = DB::table('pg_opcion_menu')->whereNull('id_padre')->where($titleCol, 'Gestión')->first();
        if (!$gestion) {
            // Si por alguna razón no existe, no forzamos creación para no romper instalaciones.
            return;
        }

        // Opción: Asistencias por empresa
        $menuId = $this->ensureMenu((string) $gestion->id, 'Asistencias por empresa', 'PgAsistenciasEmpresaIndex', 71);

        // Asignar opción a todos los roles
        if (!Schema::hasTable('pg_opcion_menu_rol') || !Schema::hasTable('roles')) {
            return;
        }

        $hasIdRol = Schema::hasColumn('pg_opcion_menu_rol', 'id_rol');
        $roleCol = $hasIdRol ? 'id_rol' : (Schema::hasColumn('pg_opcion_menu_rol', 'rol') ? 'rol' : null);
        if (!$roleCol) {
            return;
        }

        $roles = DB::table('roles')->select($hasIdRol ? ['id'] : ['id', 'name'])->get();
        foreach ($roles as $r) {
            $roleVal = $hasIdRol ? $r->id : ($r->name ?? $r->id);
            $exists = DB::table('pg_opcion_menu_rol')->where('id_opcion_menu', $menuId)->where($roleCol, $roleVal)->exists();
            if ($exists) {
                continue;
            }

            $data = ['id_opcion_menu' => $menuId, $roleCol => $roleVal];
            if (Schema::hasColumn('pg_opcion_menu_rol', 'estado')) {
                $data['estado'] = null;
            }
            if (Schema::hasColumn('pg_opcion_menu_rol', 'created_at')) {
                $data['created_at'] = now();
            }
            if (Schema::hasColumn('pg_opcion_menu_rol', 'updated_at')) {
                $data['updated_at'] = now();
            }
            if (Schema::hasColumn('pg_opcion_menu_rol', 'id')) {
                try {
                    $data['id'] = $this->nextId('pg_opcion_menu_rol', 'id');
                } catch (Throwable $e) {
                    // ignore
                }
            }

            DB::table('pg_opcion_menu_rol')->insert($data);
        }
    }

    private function ensureMenu(string $idPadre, string $titulo, string $url, int $orden): string
    {
        $titleCol = Schema::hasColumn('pg_opcion_menu', 'titulo') ? 'titulo' : (Schema::hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : 'titulo');

        $row = DB::table('pg_opcion_menu')
            ->where('id_padre', $idPadre)
            ->where($titleCol, $titulo)
            ->first();

        if ($row) {
            return (string) $row->id;
        }

        $data = [
            'id_padre' => $idPadre,
            $titleCol => $titulo,
            'url' => $url,
            'orden' => $orden,
        ];

        if (Schema::hasColumn('pg_opcion_menu', 'tipo')) {
            $data['tipo'] = 'M';
        }
        if (Schema::hasColumn('pg_opcion_menu', 'activo')) {
            $data['activo'] = 'S';
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

        if (Schema::hasColumn('pg_opcion_menu', 'id')) {
            try {
                $data['id'] = $this->nextId('pg_opcion_menu', 'id');
                DB::table('pg_opcion_menu')->insert($data);
                return (string) $data['id'];
            } catch (Throwable $e) {
                return (string) DB::table('pg_opcion_menu')->insertGetId($data);
            }
        }

        return (string) DB::table('pg_opcion_menu')->insertGetId($data);
    }

    private function nextId(string $table, string $column): string
    {
        $max = DB::table($table)->max($column);
        $n = 0;
        if ($max !== null) {
            $n = (int) preg_replace('/\D+/', '', (string) $max);
        }
        $n++;
        return str_pad((string) $n, 10, '0', STR_PAD_LEFT);
    }

    public function down(): void
    {
        // No eliminamos menú para no afectar instalaciones existentes.
    }
};
