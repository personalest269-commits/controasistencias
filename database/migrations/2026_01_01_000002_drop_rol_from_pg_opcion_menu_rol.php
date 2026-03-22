<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL: migra pg_opcion_menu_rol de "rol" (string) a "id_rol" (FK roles.id)
 * y elimina la columna rol.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_opcion_menu_rol')) {
            return;
        }

        // Asegurar columna id_rol
        if (!Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')) {
            Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
                $table->unsignedBigInteger('id_rol')->nullable()->after('id_opcion_menu');
                $table->index('id_rol', 'idx_pg_opcion_menu_rol_id_rol');
            });
        }

        // Backfill si aún existe "rol"
        if (Schema::hasTable('roles') && Schema::hasColumn('pg_opcion_menu_rol', 'rol')) {
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

        // No permitir perder data: si aún quedan NULLs, detener migración
        $nullCount = (int) DB::table('pg_opcion_menu_rol')->whereNull('id_rol')->count();
        if ($nullCount > 0) {
            throw new RuntimeException(
                "No se puede eliminar la columna 'rol' porque existen {$nullCount} registros sin 'id_rol'. " .
                "Revise roles.name vs pg_opcion_menu_rol.rol y ejecute nuevamente."
            );
        }

        // Deduplicar antes de aplicar UNIQUE (id_opcion_menu, id_rol)
        $dups = DB::table('pg_opcion_menu_rol')
            ->select('id_opcion_menu', 'id_rol', DB::raw('COUNT(*) as c'), DB::raw('MIN(id) as keep_id'))
            ->groupBy('id_opcion_menu', 'id_rol')
            ->having('c', '>', 1)
            ->get();

        foreach ($dups as $d) {
            DB::table('pg_opcion_menu_rol')
                ->where('id_opcion_menu', (int) $d->id_opcion_menu)
                ->where('id_rol', (int) $d->id_rol)
                ->where('id', '!=', (int) $d->keep_id)
                ->delete();
        }

        /**
         * IMPORTANTE (MySQL/InnoDB):
         * Existe una FK en pg_opcion_menu_rol.id_opcion_menu -> pg_opcion_menu.id.
         * La UNIQUE (id_opcion_menu, rol) suele ser el ÚNICO índice que satisface la FK.
         * Si intentamos eliminar esa UNIQUE antes de crear otro índice válido, MySQL falla con:
         *   "Cannot drop index ... needed in a foreign key constraint".
         *
         * Por eso:
         *  1) Creamos primero el nuevo UNIQUE (id_opcion_menu, id_rol) (índice válido para la FK)
         *  2) Luego eliminamos la UNIQUE antigua y la columna rol.
         */

        // 1) Crear unique nuevo (asegura índice para la FK de id_opcion_menu)
        try {
            Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
                $table->unique(['id_opcion_menu', 'id_rol'], 'uq_pg_opcion_menu_rol_opcion_rol');
            });
        } catch (Throwable $e) {
            // ignore
        }

        // 2) Dropear índices/unique antiguos y columna rol (vía SQL para poder capturar error)
        try {
            DB::statement('ALTER TABLE `pg_opcion_menu_rol` DROP INDEX `pg_opcion_menu_rol_id_opcion_menu_rol_unique`');
        } catch (Throwable $e) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE `pg_opcion_menu_rol` DROP INDEX `pg_opcion_menu_rol_rol_index`');
        } catch (Throwable $e) {
            // ignore
        }

        if (Schema::hasColumn('pg_opcion_menu_rol', 'rol')) {
            Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
                $table->dropColumn('rol');
            });
        }

        // FK id_rol -> roles.id (si existe roles)
        if (Schema::hasTable('roles')) {
            try {
                Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
                    $table->foreign('id_rol', 'fk_pg_opcion_menu_rol_roles')
                        ->references('id')->on('roles')
                        ->onUpdate('cascade')->onDelete('cascade');
                });
            } catch (Throwable $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        // No se revierte automáticamente para no reintroducir inconsistencias.
    }
};
