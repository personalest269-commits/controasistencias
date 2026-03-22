<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nota: este proyecto ha tenido varias evoluciones del esquema (IDs varchar, columnas renombradas).
        // Esta migración intenta ser compatible detectando columnas existentes antes de usarlas.

        // 1) Permisos
        $this->ensurePermission('pg_departamento', 'Departamentos', 'Gestión de departamentos');
        $this->ensurePermission('pg_eventos', 'Eventos', 'Gestión de eventos/calendario');

        // Asignar permisos a todos los roles existentes
        $roleCols = ['id'];
        if ($this->hasColumn('roles', 'name')) {
            $roleCols[] = 'name';
        }
        $roles = DB::table('roles')->select($roleCols)->get();
        $perms = DB::table('pg_permisos')->whereIn('name', ['pg_departamento', 'pg_eventos'])->get()->keyBy('name');
        foreach ($roles as $r) {
            foreach (['pg_departamento', 'pg_eventos'] as $pname) {
                $pid = $perms[$pname]->id ?? null;
                if (!$pid) continue;
                $exists = DB::table('pg_permisos_role')
                    ->where('permission_id', $pid)
                    ->where('role_id', $r->id)
                    ->exists();
                if (!$exists) {
                    $data = [
                        'permission_id' => $pid,
                        'role_id' => $r->id,
                    ];
                    // Si el pivot tiene id y no es autoincrement, generar.
                    if ($this->hasColumn('pg_permisos_role', 'id') && !$this->isAutoIncrement('pg_permisos_role', 'id')) {
                        $data['id'] = $this->nextId('pg_permisos_role', 'id');
                    }
                    DB::table('pg_permisos_role')->insert($data);
                }
            }
        }

        // 2) Menú
        $titleCol = $this->hasColumn('pg_opcion_menu', 'titulo')
            ? 'titulo'
            : ($this->hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);

        if (!$titleCol) {
            // No tenemos una columna de título conocida, no rompemos.
            return;
        }

        $gestion = DB::table('pg_opcion_menu')->where($titleCol, 'Gestión')->first();
        if (!$gestion) {
            // Si no existe, no rompemos.
            return;
        }

        // OJO: en este proyecto varios IDs son VARCHAR(10) y ya no son auto_increment.
        // Por eso NO casteamos a int.
        $idDep = $this->ensureMenu((string) $gestion->id, 'Departamentos', 'PgDepartamentosIndex', 50);
        $idEvt = $this->ensureMenu((string) $gestion->id, 'Eventos', 'PgEventosIndex', 60);

        // Asignar menú a todos los roles
        foreach ($roles as $r) {
            foreach ([$idDep, $idEvt] as $mid) {
                if (!$mid) continue;
                // Hay variantes del esquema:
                // - pg_opcion_menu_rol(id_opcion_menu, id_rol)
                // - pg_opcion_menu_rol(id_opcion_menu, rol)
                $hasIdRol = $this->hasColumn('pg_opcion_menu_rol', 'id_rol');
                $roleCol = $hasIdRol ? 'id_rol' : 'rol';

                $existsQ = DB::table('pg_opcion_menu_rol')->where('id_opcion_menu', $mid);
                $existsQ = $existsQ->where($roleCol, $hasIdRol ? $r->id : ($r->name ?? $r->id));

                if (!$existsQ->exists()) {
                    $data = ['id_opcion_menu' => $mid, $roleCol => ($hasIdRol ? $r->id : ($r->name ?? $r->id))];

                    if ($this->hasColumn('pg_opcion_menu_rol', 'estado')) {
                        $data['estado'] = null;
                    }
                    if ($this->hasColumn('pg_opcion_menu_rol', 'created_at')) {
                        $data['created_at'] = now();
                    }
                    if ($this->hasColumn('pg_opcion_menu_rol', 'updated_at')) {
                        $data['updated_at'] = now();
                    }

                    if ($this->hasColumn('pg_opcion_menu_rol', 'id') && !$this->isAutoIncrement('pg_opcion_menu_rol', 'id')) {
                        $data['id'] = $this->nextId('pg_opcion_menu_rol', 'id');
                    }

                    DB::table('pg_opcion_menu_rol')->insert($data);
                }
            }
        }
    }

    private function ensurePermission(string $name, string $displayName, string $description): void
    {
        $exists = DB::table('pg_permisos')->where('name', $name)->exists();
        if (!$exists) {
            $data = [
                'name' => $name,
                'display_name' => $displayName,
                'description' => $description,
            ];

            if ($this->hasColumn('pg_permisos', 'created_at')) {
                $data['created_at'] = now();
            }
            if ($this->hasColumn('pg_permisos', 'updated_at')) {
                $data['updated_at'] = now();
            }

            // En este proyecto, permissions.id puede ser VARCHAR(10) sin AUTO_INCREMENT.
            if ($this->hasColumn('pg_permisos', 'id') && !$this->isAutoIncrement('pg_permisos', 'id')) {
                $data['id'] = $this->nextId('pg_permisos', 'id');
            }

            DB::table('pg_permisos')->insert($data);
        }
    }

    private function ensureMenu(string $padreId, string $titulo, string $url, int $orden): ?string
    {
        $titleCol = $this->hasColumn('pg_opcion_menu', 'titulo')
            ? 'titulo'
            : ($this->hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);

        if (!$titleCol) {
            return null;
        }

        $row = DB::table('pg_opcion_menu')
            ->where($titleCol, $titulo)
            ->where('id_padre', $padreId)
            ->first();

        if ($row) {
            return (string) $row->id;
        }

        $data = [
            'id_padre' => $padreId,
            $titleCol => $titulo,
            'url' => $url,
            'orden' => $orden,
        ];

        // Campos opcionales según versión del esquema
        if ($this->hasColumn('pg_opcion_menu', 'tipo')) {
            $data['tipo'] = 'M';
        }
        if ($this->hasColumn('pg_opcion_menu', 'activo')) {
            $data['activo'] = 'S';
        }
        if ($this->hasColumn('pg_opcion_menu', 'estado')) {
            $data['estado'] = null;
        }
        if ($this->hasColumn('pg_opcion_menu', 'created_at')) {
            $data['created_at'] = now();
        }
        if ($this->hasColumn('pg_opcion_menu', 'updated_at')) {
            $data['updated_at'] = now();
        }

        // En ambientes donde pg_opcion_menu.id ya es VARCHAR(10) sin AUTO_INCREMENT
        if ($this->hasColumn('pg_opcion_menu', 'id') && !$this->isAutoIncrement('pg_opcion_menu', 'id')) {
            $data['id'] = $this->nextId('pg_opcion_menu', 'id');
            DB::table('pg_opcion_menu')->insert($data);
            return (string) $data['id'];
        }

        // Caso clásico con auto_increment
        $id = DB::table('pg_opcion_menu')->insertGetId($data);
        return $id ? (string) $id : null;
    }

    private function hasColumn(string $table, string $column): bool
    {
        // Preferimos SHOW COLUMNS porque algunos usuarios no tienen permisos sobre information_schema.
        try {
            $rows = DB::select("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]);
            return count($rows) > 0;
        } catch (Throwable $e) {
            // fallback info_schema
            try {
                $row = DB::selectOne(
                    'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $column]
                );
                return (int) ($row->c ?? 0) > 0;
            } catch (Throwable $e2) {
                return false;
            }
        }
    }

    private function isAutoIncrement(string $table, string $column = 'id'): bool
    {
        try {
            $rows = DB::select("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]);
            if (count($rows) > 0) {
                // SHOW COLUMNS devuelve 'Extra'
                $extra = '';
                $r = (array) $rows[0];
                if (isset($r['Extra'])) $extra = (string) $r['Extra'];
                if (isset($r['extra'])) $extra = (string) $r['extra'];
                $extra = strtolower($extra);
                return str_contains($extra, 'auto_increment');
            }
        } catch (Throwable $e) {
            // ignore y probamos info_schema
        }

        try {
            $row = DB::selectOne(
                'SELECT EXTRA AS e FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            );
            $extra = strtolower((string) ($row->e ?? ''));
            return str_contains($extra, 'auto_increment');
        } catch (Throwable $e2) {
            return false;
        }
    }

    private function nextId(string $table, string $column = 'id'): string
    {
        // Calcula el siguiente ID numérico y lo rellena a 10 dígitos: 0000000001
        $max = 0;
        try {
            $row = DB::selectOne("SELECT MAX(CAST(`{$column}` AS UNSIGNED)) AS m FROM `{$table}`");
            $max = (int) ($row->m ?? 0);
        } catch (Throwable $e) {
            $max = 0;
        }
        $next = $max + 1;
        return str_pad((string) $next, 10, '0', STR_PAD_LEFT);
    }

    public function down(): void
    {
        // No hacemos rollback para no romper menús/permisos si ya se usan.
    }
};
