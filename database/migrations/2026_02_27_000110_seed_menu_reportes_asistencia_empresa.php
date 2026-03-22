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

        $reportes = DB::table('pg_opcion_menu')
            ->whereNull('id_padre')
            ->where($titleCol, 'Reportes')
            ->first();
        if (!$reportes) {
            return;
        }

        // Child: asistencia por empresa
        $child = DB::table('pg_opcion_menu')
            ->where('id_padre', (string) $reportes->id)
            ->where($titleCol, 'Asistencia por empresa')
            ->first();

        if (!$child) {
            $data = [
                'id_padre' => (string) $reportes->id,
                $titleCol => 'Asistencia por empresa',
                'url' => 'PgAsistenciasEmpresaReportes',
                'orden' => 11,
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
                    $childId = (string) $data['id'];
                } catch (Throwable $e) {
                    $childId = (string) DB::table('pg_opcion_menu')->insertGetId($data);
                }
            } else {
                $childId = (string) DB::table('pg_opcion_menu')->insertGetId($data);
            }
        } else {
            $childId = (string) $child->id;
        }

        // Asignar el menú a todos los roles
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
            $exists = DB::table('pg_opcion_menu_rol')->where('id_opcion_menu', $childId)->where($roleCol, $roleVal)->exists();
            if ($exists) {
                continue;
            }
            $data = ['id_opcion_menu' => $childId, $roleCol => $roleVal];
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
