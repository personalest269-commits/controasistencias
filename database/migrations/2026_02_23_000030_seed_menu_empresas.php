<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Permiso
        $this->ensurePermission('pg_empresa', 'Empresas', 'Gestión de empresas');

        // Asignar permiso a todos los roles existentes
        $roles = DB::table('roles')->select(['id', DB::raw('COALESCE(name,id) as name')])->get();
        $perm = DB::table('pg_permisos')->where('name', 'pg_empresa')->first();
        if ($perm) {
            foreach ($roles as $r) {
                $exists = DB::table('pg_permisos_role')
                    ->where('permission_id', $perm->id)
                    ->where('role_id', $r->id)
                    ->exists();
                if (!$exists) {
                    $data = [
                        'permission_id' => $perm->id,
                        'role_id' => $r->id,
                    ];
                    if ($this->hasColumn('pg_permisos_role', 'id') && !$this->isAutoIncrement('pg_permisos_role', 'id')) {
                        $data['id'] = $this->nextId('pg_permisos_role', 'id');
                    }
                    DB::table('pg_permisos_role')->insert($data);
                }
            }
        }

        // 2) Menú (bajo "Gestión")
        $titleCol = $this->hasColumn('pg_opcion_menu', 'titulo')
            ? 'titulo'
            : ($this->hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);
        if (!$titleCol) return;

        $gestion = DB::table('pg_opcion_menu')->where($titleCol, 'Gestión')->first();
        if (!$gestion) return;

        $idEmp = $this->ensureMenu((string) $gestion->id, 'Empresas', 'PgEmpresasIndex', 45);

        if ($idEmp) {
            foreach ($roles as $r) {
                $hasIdRol = $this->hasColumn('pg_opcion_menu_rol', 'id_rol');
                $roleCol = $hasIdRol ? 'id_rol' : 'rol';

                $existsQ = DB::table('pg_opcion_menu_rol')->where('id_opcion_menu', $idEmp);
                $existsQ = $existsQ->where($roleCol, $hasIdRol ? $r->id : ($r->name ?? $r->id));

                if (!$existsQ->exists()) {
                    $data = ['id_opcion_menu' => $idEmp, $roleCol => ($hasIdRol ? $r->id : ($r->name ?? $r->id))];

                    if ($this->hasColumn('pg_opcion_menu_rol', 'estado')) $data['estado'] = null;
                    if ($this->hasColumn('pg_opcion_menu_rol', 'created_at')) $data['created_at'] = now();
                    if ($this->hasColumn('pg_opcion_menu_rol', 'updated_at')) $data['updated_at'] = now();

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
        if ($exists) return;

        $data = [
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
        ];
        if ($this->hasColumn('pg_permisos', 'created_at')) $data['created_at'] = now();
        if ($this->hasColumn('pg_permisos', 'updated_at')) $data['updated_at'] = now();
        if ($this->hasColumn('pg_permisos', 'id') && !$this->isAutoIncrement('pg_permisos', 'id')) {
            $data['id'] = $this->nextId('pg_permisos', 'id');
        }
        DB::table('pg_permisos')->insert($data);
    }

    private function ensureMenu(string $padreId, string $titulo, string $url, int $orden): ?string
    {
        $titleCol = $this->hasColumn('pg_opcion_menu', 'titulo')
            ? 'titulo'
            : ($this->hasColumn('pg_opcion_menu', 'descripcion') ? 'descripcion' : null);
        if (!$titleCol) return null;

        $row = DB::table('pg_opcion_menu')
            ->where($titleCol, $titulo)
            ->where('id_padre', $padreId)
            ->first();
        if ($row) return (string) $row->id;

        $data = [
            'id_padre' => $padreId,
            $titleCol => $titulo,
            'url' => $url,
            'orden' => $orden,
        ];
        if ($this->hasColumn('pg_opcion_menu', 'tipo')) $data['tipo'] = 'M';
        if ($this->hasColumn('pg_opcion_menu', 'activo')) $data['activo'] = 'S';
        if ($this->hasColumn('pg_opcion_menu', 'estado')) $data['estado'] = null;
        if ($this->hasColumn('pg_opcion_menu', 'created_at')) $data['created_at'] = now();
        if ($this->hasColumn('pg_opcion_menu', 'updated_at')) $data['updated_at'] = now();

        if ($this->hasColumn('pg_opcion_menu', 'id') && !$this->isAutoIncrement('pg_opcion_menu', 'id')) {
            $data['id'] = $this->nextId('pg_opcion_menu', 'id');
            DB::table('pg_opcion_menu')->insert($data);
            return (string) $data['id'];
        }

        $id = DB::table('pg_opcion_menu')->insertGetId($data);
        return $id ? (string) $id : null;
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            $rows = DB::select("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column]);
            return count($rows) > 0;
        } catch (Throwable $e) {
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
                $r = (array) $rows[0];
                $extra = (string) ($r['Extra'] ?? $r['extra'] ?? '');
                return str_contains(strtolower($extra), 'auto_increment');
            }
        } catch (Throwable $e) {
            // ignore
        }
        return false;
    }

    private function nextId(string $table, string $column = 'id'): string
    {
        $max = DB::table($table)->max($column);
        $n = is_numeric($max) ? ((int) $max + 1) : 1;
        return (string) $n;
    }
};
