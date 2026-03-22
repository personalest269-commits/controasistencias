<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_opcion_menu_rol')) {
            return;
        }

        if (!Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')) {
            Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
                $table->unsignedBigInteger('id_rol')->nullable()->after('id_opcion_menu');
                $table->index('id_rol', 'idx_pg_opcion_menu_rol_id_rol');
            });
        }

        // Backfill: rol (name) -> roles.id
        if (
            Schema::hasTable('roles')
            && Schema::hasColumn('pg_opcion_menu_rol', 'rol')
            && Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')
        ) {
            $roleMap = DB::table('roles')->pluck('id', 'name')->toArray();

            DB::table('pg_opcion_menu_rol')
                ->select('id', 'rol')
                ->whereNull('id_rol')
                ->whereNotNull('rol')
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($roleMap) {
                    foreach ($rows as $r) {
                        $rolName = (string) $r->rol;
                        if (isset($roleMap[$rolName])) {
                            DB::table('pg_opcion_menu_rol')
                                ->where('id', $r->id)
                                ->update(['id_rol' => (int) $roleMap[$rolName]]);
                        }
                    }
                });
        }

        // FK (si no existe)
        if (Schema::hasTable('roles') && Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')) {
            try {
                Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
                    $table->foreign('id_rol', 'fk_pg_opcion_menu_rol_roles')
                        ->references('id')->on('roles')
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                });
            } catch (\Throwable $e) {
                // ignore (ya existe o no soportado)
            }
        }
    }

    public function down(): void
    {
        // No se elimina en down para evitar romper datos existentes.
    }
};
