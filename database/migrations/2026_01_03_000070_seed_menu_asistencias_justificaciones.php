<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permisos
        $this->ensurePermission('pg_asistencias', 'Asistencias', 'Gestión de asistencias por evento');
        $this->ensurePermission('pg_justificaciones', 'Justificaciones', 'Gestión de justificaciones de asistencia');
        $this->ensurePermission('pg_reporte_asistencias', 'Reporte Asistencias', 'Reportes de asistencia');

        // Asignar permisos a todos los roles
        if (Schema::hasTable('roles') && Schema::hasTable('pg_permisos') && Schema::hasTable('pg_permisos_role')) {
            $roles = DB::table('roles')->select(['id'])->get();
            $perms = DB::table('pg_permisos')->whereIn('name', ['pg_asistencias', 'pg_justificaciones', 'pg_reporte_asistencias'])->get()->keyBy('name');
            foreach ($roles as $r) {
                foreach (['pg_asistencias', 'pg_justificaciones', 'pg_reporte_asistencias'] as $pname) {
                    $pid = $perms[$pname]->id ?? null;
                    if (!$pid) continue;
                    $exists = DB::table('pg_permisos_role')->where('permission_id', $pid)->where('role_id', $r->id)->exists();
                    if (!$exists) {
                        $data = ['permission_id' => $pid, 'role_id' => $r->id];
                        if (Schema::hasColumn('pg_permisos_role', 'id')) {
                            // si no es autoincrement, generamos (asumimos varchar 10)
                            try {
                                $data['id'] = $this->nextId('pg_permisos_role', 'id');
                            } catch (Throwable $e) {
                                // ignore
                            }
                        }
                        DB::table('pg_permisos_role')->insert($data);
                    }
                }
            }
        }

        // Menú (si existe)
        if (!Schema::hasTable('pg_opcion_menu')) {
            return;
        }

        $titleCol = Schema::hasColumn('pg_opcion_menu', 'titulo') ? 'titulo' : (Schema::hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);
        if (!$titleCol) {
            return;
        }

        $gestion = DB::table('pg_opcion_menu')->where($titleCol, 'Gestión')->first();
        if (!$gestion) {
            return;
        }

        $idAsis = $this->ensureMenu((string) $gestion->id, 'Asistencias', 'PgAsistenciasIndex', 70);
        $idJust = $this->ensureMenu((string) $gestion->id, 'Justificaciones', 'PgJustificacionesIndex', 80);

        if (!Schema::hasTable('pg_opcion_menu_rol')) {
            return;
        }

        $hasIdRol = Schema::hasColumn('pg_opcion_menu_rol', 'id_rol');
        $roleCol = $hasIdRol ? 'id_rol' : (Schema::hasColumn('pg_opcion_menu_rol', 'rol') ? 'rol' : null);
        if (!$roleCol) {
            return;
        }

        $roles = Schema::hasTable('roles') ? DB::table('roles')->select($hasIdRol ? ['id'] : ['id', 'name'])->get() : collect();
        foreach ($roles as $r) {
            foreach ([$idAsis, $idJust] as $mid) {
                if (!$mid) continue;

                $roleVal = $hasIdRol ? $r->id : ($r->name ?? $r->id);
                $exists = DB::table('pg_opcion_menu_rol')->where('id_opcion_menu', $mid)->where($roleCol, $roleVal)->exists();
                if (!$exists) {
                    $data = ['id_opcion_menu' => $mid, $roleCol => $roleVal];
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
        }
    }

    private function ensurePermission(string $name, string $displayName, string $description): void
    {
        if (!Schema::hasTable('pg_permisos')) {
            return;
        }

        $exists = DB::table('pg_permisos')->where('name', $name)->exists();
        if ($exists) {
            return;
        }

        $data = [
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
        ];
        if (Schema::hasColumn('pg_permisos', 'created_at')) {
            $data['created_at'] = now();
        }
        if (Schema::hasColumn('pg_permisos', 'updated_at')) {
            $data['updated_at'] = now();
        }
        if (Schema::hasColumn('pg_permisos', 'id')) {
            // si no es autoincrement, generamos (asumimos varchar 10)
            try {
                $data['id'] = $this->nextId('pg_permisos', 'id');
            } catch (Throwable $e) {
                // ignore
            }
        }

        DB::table('pg_permisos')->insert($data);
    }

    private function ensureMenu(string $padreId, string $titulo, string $url, int $orden): ?string
    {
        if (!Schema::hasTable('pg_opcion_menu')) {
            return null;
        }

        $titleCol = Schema::hasColumn('pg_opcion_menu', 'titulo') ? 'titulo' : (Schema::hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);
        if (!$titleCol) {
            return null;
        }

        $row = DB::table('pg_opcion_menu')->where('id_padre', $padreId)->where($titleCol, $titulo)->first();
        if ($row) {
            return (string) $row->id;
        }

        $data = [
            'id_padre' => $padreId,
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
            // Si es varchar y no autoincrement, generamos. Si es autoincrement, insertGetId funcionará.
            try {
                $data['id'] = $this->nextId('pg_opcion_menu', 'id');
                DB::table('pg_opcion_menu')->insert($data);
                return (string) $data['id'];
            } catch (Throwable $e) {
                // fallback
            }
        }

        $id = DB::table('pg_opcion_menu')->insertGetId($data);
        return $id ? (string) $id : null;
    }

    private function nextId(string $table, string $column): string
    {
        // Generación simple: max(id)+1 con padding a 10.
        // Compatible con varchar numérico 0000000001.
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
        // No eliminamos permisos/menú para evitar afectar instalaciones existentes.
    }
};
