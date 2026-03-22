<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Renombrar tablas base
        if (Schema::hasTable('idiomas') && !Schema::hasTable('pg_idiomas')) {
            Schema::rename('idiomas', 'pg_idiomas');
        }

        if (Schema::hasTable('permissions') && !Schema::hasTable('pg_permisos')) {
            Schema::rename('permissions', 'pg_permisos');
        }

        if (Schema::hasTable('permission_role') && !Schema::hasTable('pg_permisos_role')) {
            Schema::rename('permission_role', 'pg_permisos_role');
        }

        // 2) Asegurar columna id_dioma en pg_general_traduccion y relacionarla
        if (Schema::hasTable('pg_general_traduccion')) {
            if (!Schema::hasColumn('pg_general_traduccion', 'id_dioma')) {
                Schema::table('pg_general_traduccion', function (Blueprint $table) {
                    $table->string('id_dioma', 10)->nullable()->index('idx_pg_general_traduccion_id_dioma');
                });
            }

            // Backfill: id_dioma desde idioma_codigo
            if (Schema::hasTable('pg_idiomas') && Schema::hasColumn('pg_idiomas', 'codigo')) {
                DB::table('pg_general_traduccion as t')
                    ->leftJoin('pg_idiomas as i', 'i.codigo', '=', 't.idioma_codigo')
                    ->whereNull('t.id_dioma')
                    ->update(['t.id_dioma' => DB::raw('i.id')]);
            }

            // FK best-effort (evitar romper en engines sin soporte)
            try {
                // Drop si ya existían con otros nombres
                $this->dropFkIfExists('pg_general_traduccion', 'fk_pg_general_traduccion_idiomas_codigo');
                $this->dropFkIfExists('pg_general_traduccion', 'fk_pg_general_traduccion_pg_idiomas_id');

                Schema::table('pg_general_traduccion', function (Blueprint $table) {
                    if (Schema::hasColumn('pg_general_traduccion', 'idioma_codigo')) {
                        $table->foreign('idioma_codigo', 'fk_pg_general_traduccion_idiomas_codigo')
                            ->references('codigo')->on('pg_idiomas')
                            ->onUpdate('cascade')->onDelete('restrict');
                    }
                    if (Schema::hasColumn('pg_general_traduccion', 'id_dioma')) {
                        $table->foreign('id_dioma', 'fk_pg_general_traduccion_pg_idiomas_id')
                            ->references('id')->on('pg_idiomas')
                            ->onUpdate('cascade')->onDelete('restrict');
                    }
                });
            } catch (\Throwable $e) {
                // no-op
            }
        }

        // 3) Arreglar posibles FKs que aún apunten a nombres antiguos (best-effort)
        // email_plantillas_traduccion.idioma_id -> pg_idiomas.id
        try {
            if (Schema::hasTable('email_plantillas_traduccion') && Schema::hasTable('pg_idiomas')) {
                // Intentar eliminar FK vieja si existe
                $this->dropFkIfExists('email_plantillas_traduccion', 'email_plantillas_traduccion_idioma_id_fk');
                Schema::table('email_plantillas_traduccion', function (Blueprint $table) {
                    if (Schema::hasColumn('email_plantillas_traduccion', 'idioma_id')) {
                        $table->foreign('idioma_id', 'email_plantillas_traduccion_idioma_id_fk')
                            ->references('id')->on('pg_idiomas')
                            ->onUpdate('cascade')->onDelete('restrict');
                    }
                });
            }
        } catch (\Throwable $e) {
            // no-op
        }

        // permission_role FKs (si existen en tu entorno)
        try {
            if (Schema::hasTable('pg_permisos_role') && Schema::hasTable('pg_permisos')) {
                $this->dropFkIfExists('pg_permisos_role', 'fk_pg_permisos_role_perm');
                $this->dropFkIfExists('pg_permisos_role', 'fk_pg_permisos_role_rol');

                Schema::table('pg_permisos_role', function (Blueprint $table) {
                    if (Schema::hasColumn('pg_permisos_role', 'permission_id')) {
                        $table->foreign('permission_id', 'fk_pg_permisos_role_perm')
                            ->references('id')->on('pg_permisos')
                            ->onUpdate('cascade')->onDelete('cascade');
                    }
                    if (Schema::hasColumn('pg_permisos_role', 'role_id')) {
                        $table->foreign('role_id', 'fk_pg_permisos_role_rol')
                            ->references('id')->on('roles')
                            ->onUpdate('cascade')->onDelete('cascade');
                    }
                });
            }
        } catch (\Throwable $e) {
            // no-op
        }
    }

    public function down(): void
    {
        // Reversión best-effort (si lo necesitas)
        if (Schema::hasTable('pg_idiomas') && !Schema::hasTable('idiomas')) {
            Schema::rename('pg_idiomas', 'idiomas');
        }
        if (Schema::hasTable('pg_permisos') && !Schema::hasTable('permissions')) {
            Schema::rename('pg_permisos', 'permissions');
        }
        if (Schema::hasTable('pg_permisos_role') && !Schema::hasTable('permission_role')) {
            Schema::rename('pg_permisos_role', 'permission_role');
        }
    }

    private function dropFkIfExists(string $table, string $fkName): void
    {
        try {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
