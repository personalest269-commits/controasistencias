<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega la opción de menú "Configuraciones fronted".
 */
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

        // Root "Account Settings" (seed base)
        $root = DB::table('pg_opcion_menu')->whereNull('id_padre')->where($titleCol, 'Account Settings')->first();
        if (!$root) {
            // fallback: usar "Administración" si no existe
            $root = DB::table('pg_opcion_menu')->whereNull('id_padre')->whereIn($titleCol, ['Administración', 'Administracion'])->first();
        }
        if (!$root) {
            return;
        }
        $rootId = (string) $root->id;

        $exists = DB::table('pg_opcion_menu')
            ->where('id_padre', $rootId)
            ->where($titleCol, 'Configuraciones fronted')
            ->exists();
        if (!$exists) {
            $data = [
                'id_padre' => $rootId,
                $titleCol => 'Configuraciones fronted',
                'url' => 'FrFrontedIndex',
                'orden' => 98,
            ];
            if (Schema::hasColumn('pg_opcion_menu', 'tipo')) $data['tipo'] = 'M';
            if (Schema::hasColumn('pg_opcion_menu', 'activo')) $data['activo'] = 'S';
            if (Schema::hasColumn('pg_opcion_menu', 'estado')) $data['estado'] = null;
            if (Schema::hasColumn('pg_opcion_menu', 'created_at')) $data['created_at'] = now();
            if (Schema::hasColumn('pg_opcion_menu', 'updated_at')) $data['updated_at'] = now();

            if (Schema::hasColumn('pg_opcion_menu', 'id')) {
                $data['id'] = $this->nextId('pg_opcion_menu', 'id');
                DB::table('pg_opcion_menu')->insert($data);
                $childId = (string) $data['id'];
            } else {
                $childId = (string) DB::table('pg_opcion_menu')->insertGetId($data);
            }

            // Asignar a todos los roles
            if (Schema::hasTable('pg_opcion_menu_rol') && Schema::hasTable('roles')) {
                $hasIdRol = Schema::hasColumn('pg_opcion_menu_rol', 'id_rol');
                $roleCol = $hasIdRol ? 'id_rol' : (Schema::hasColumn('pg_opcion_menu_rol', 'rol') ? 'rol' : null);
                if ($roleCol) {
                    $roles = DB::table('roles')->select($hasIdRol ? ['id'] : ['id', 'name'])->get();
                    foreach ($roles as $r) {
                        $roleVal = $hasIdRol ? $r->id : ($r->name ?? $r->id);
                        $pivotExists = DB::table('pg_opcion_menu_rol')->where('id_opcion_menu', $childId)->where($roleCol, $roleVal)->exists();
                        if ($pivotExists) continue;
                        $p = ['id_opcion_menu' => $childId, $roleCol => $roleVal];
                        if (Schema::hasColumn('pg_opcion_menu_rol', 'estado')) $p['estado'] = null;
                        if (Schema::hasColumn('pg_opcion_menu_rol', 'created_at')) $p['created_at'] = now();
                        if (Schema::hasColumn('pg_opcion_menu_rol', 'updated_at')) $p['updated_at'] = now();
                        if (Schema::hasColumn('pg_opcion_menu_rol', 'id')) {
                            try { $p['id'] = $this->nextId('pg_opcion_menu_rol', 'id'); } catch (Throwable $e) {}
                        }
                        DB::table('pg_opcion_menu_rol')->insert($p);
                    }
                }
            }
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
        // No-op
    }
};
