<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte a VARCHAR(10) los IDs solicitados y sus llaves foráneas.
 *
 * Tablas afectadas:
 * - roles(id), permissions(id), role_user(role_id), permission_role(permission_id, role_id)
 * - pg_opcion_menu(id, id_padre, id_archivo)
 * - pg_opcion_menu_rol(id, id_opcion_menu, id_rol)
 * - pg_persona_foto(id, id_archivo)
 * - ad_archivo_digital(id)
 * - pg_idiomas(id)
 * - email_configuraciones(id), email_plantillas(id), email_plantillas_traduccion(id, email_template_id, idioma_id)
 * - pg_estado_civil(id)
 * - pg_tipo_identificacion(id)
 * - pg_configuraciones(id)
 *
 * Además:
 * - pg_usuario: agrega id_archivo (nullable) y FK a ad_archivo_digital(id)
 */
return new class extends Migration
{
    private function dropAllForeignKeys(string $table): void
    {
        try {
            $fks = DB::select(
                "SELECT CONSTRAINT_NAME AS c FROM information_schema.KEY_COLUMN_USAGE\n" .
                "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$table]
            );

            foreach ($fks as $fk) {
                $name = (string) ($fk->c ?? '');
                if ($name !== '') {
                    try {
                        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
                    } catch (Throwable $e) {
                        // ignore
                    }
                }
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    private function dropPrimaryKey(string $table): void
    {
        try {
            DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");
        } catch (Throwable $e) {
            // ignore
        }
    }

    private function convertBigIdToVarchar(string $table, string $col = 'id', bool $nullable = false): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $col)) {
            return;
        }

        // Quitar AUTO_INCREMENT si existe (bigIncrements)
        try {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$col}` BIGINT UNSIGNED " . ($nullable ? 'NULL' : 'NOT NULL'));
        } catch (Throwable $e) {
            // ignore
        }

        // Convertir a VARCHAR(10)
        try {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$col}` VARCHAR(10) " . ($nullable ? 'NULL' : 'NOT NULL'));
        } catch (Throwable $e) {
            // ignore
        }
    }

    private function convertBigColumnToVarchar(string $table, string $col, bool $nullable = false): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $col)) {
            return;
        }

        try {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$col}` VARCHAR(10) " . ($nullable ? 'NULL' : 'NOT NULL'));
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1) Soltar FKs antes de cambiar tipos
        foreach ([
            'role_user',
            'pg_permisos_role',
            'pg_opcion_menu',
            'pg_opcion_menu_rol',
            'pg_persona_foto',
            'email_plantillas_traduccion',
            'pg_usuario',
        ] as $t) {
            if (Schema::hasTable($t)) {
                $this->dropAllForeignKeys($t);
            }
        }

        // 2) Cambiar IDs principales a VARCHAR(10)
        $this->convertBigIdToVarchar('roles', 'id', false);
        $this->convertBigIdToVarchar('pg_permisos', 'id', false);
        $this->convertBigIdToVarchar('pg_opcion_menu', 'id', false);
        $this->convertBigIdToVarchar('pg_opcion_menu_rol', 'id', false);
        $this->convertBigIdToVarchar('pg_configuraciones', 'id', false);
        $this->convertBigIdToVarchar('email_configuraciones', 'id', false);
        $this->convertBigIdToVarchar('email_plantillas', 'id', false);
        $this->convertBigIdToVarchar('email_plantillas_traduccion', 'id', false);
        $this->convertBigIdToVarchar('pg_idiomas', 'id', false);
        $this->convertBigIdToVarchar('pg_estado_civil', 'id', false);
        $this->convertBigIdToVarchar('pg_tipo_identificacion', 'id', false);
        $this->convertBigIdToVarchar('ad_archivo_digital', 'id', false);
        $this->convertBigIdToVarchar('pg_persona_foto', 'id', false);

        // 3) Cambiar columnas FK a VARCHAR(10)
        // role_user
        $this->convertBigColumnToVarchar('role_user', 'role_id', false);

        // permission_role
        $this->convertBigColumnToVarchar('pg_permisos_role', 'permission_id', false);
        $this->convertBigColumnToVarchar('pg_permisos_role', 'role_id', false);

        // pg_opcion_menu
        $this->convertBigColumnToVarchar('pg_opcion_menu', 'id_padre', true);
        $this->convertBigColumnToVarchar('pg_opcion_menu', 'id_archivo', true);

        // pg_opcion_menu_rol
        $this->convertBigColumnToVarchar('pg_opcion_menu_rol', 'id_opcion_menu', false);
        $this->convertBigColumnToVarchar('pg_opcion_menu_rol', 'id_rol', true);

        // pg_persona_foto
        $this->convertBigColumnToVarchar('pg_persona_foto', 'id_archivo', false);

        // email_plantillas_traduccion
        $this->convertBigColumnToVarchar('email_plantillas_traduccion', 'email_template_id', false);
        $this->convertBigColumnToVarchar('email_plantillas_traduccion', 'idioma_id', false);

        // 4) Re-crear PK compuestas de pivotes si es necesario
        if (Schema::hasTable('role_user')) {
            $this->dropPrimaryKey('role_user');
            try {
                DB::statement('ALTER TABLE `role_user` ADD PRIMARY KEY (`usuario_id`, `role_id`)');
            } catch (Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('pg_permisos_role')) {
            $this->dropPrimaryKey('pg_permisos_role');
            try {
                DB::statement('ALTER TABLE `permission_role` ADD PRIMARY KEY (`permission_id`, `role_id`)');
            } catch (Throwable $e) {
                // ignore
            }
        }

        // 5) Agregar campo id_archivo a pg_usuario (nullable)
        if (Schema::hasTable('pg_usuario') && !Schema::hasColumn('pg_usuario', 'id_archivo')) {
            Schema::table('pg_usuario', function (Blueprint $table) {
                // varchar(10) porque ad_archivo_digital.id pasa a varchar(10)
                $table->string('id_archivo', 10)->nullable()->after('id_persona');
                $table->index('id_archivo', 'idx_pg_usuario_id_archivo');
            });
        }

        // 6) Re-crear FKs (best-effort)
        // role_user -> roles
        if (Schema::hasTable('role_user') && Schema::hasTable('roles')) {
            try {
                DB::statement('ALTER TABLE `role_user` ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (Throwable $e) {
                // ignore
            }
        }

        // permission_role -> roles/permissions
        if (Schema::hasTable('pg_permisos_role') && Schema::hasTable('roles')) {
            try {
                DB::statement('ALTER TABLE `permission_role` ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (Throwable $e) {
                // ignore
            }
        }
        if (Schema::hasTable('pg_permisos_role') && Schema::hasTable('pg_permisos')) {
            try {
                DB::statement('ALTER TABLE `permission_role` ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (Throwable $e) {
                // ignore
            }
        }

        // pg_opcion_menu self + archivo
        if (Schema::hasTable('pg_opcion_menu')) {
            try {
                DB::statement('ALTER TABLE `pg_opcion_menu` ADD CONSTRAINT `pg_opcion_menu_id_padre_foreign` FOREIGN KEY (`id_padre`) REFERENCES `pg_opcion_menu`(`id`) ON UPDATE CASCADE ON DELETE SET NULL');
            } catch (Throwable $e) {
                // ignore
            }
            if (Schema::hasTable('ad_archivo_digital')) {
                try {
                    DB::statement('ALTER TABLE `pg_opcion_menu` ADD CONSTRAINT `pg_opcion_menu_id_archivo_foreign` FOREIGN KEY (`id_archivo`) REFERENCES `ad_archivo_digital`(`id`) ON UPDATE CASCADE ON DELETE SET NULL');
                } catch (Throwable $e) {
                    // ignore
                }
            }
        }

        // pg_opcion_menu_rol
        if (Schema::hasTable('pg_opcion_menu_rol') && Schema::hasTable('pg_opcion_menu')) {
            try {
                DB::statement('ALTER TABLE `pg_opcion_menu_rol` ADD CONSTRAINT `pg_opcion_menu_rol_id_opcion_menu_foreign` FOREIGN KEY (`id_opcion_menu`) REFERENCES `pg_opcion_menu`(`id`) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (Throwable $e) {
                // ignore
            }
        }
        if (Schema::hasTable('pg_opcion_menu_rol') && Schema::hasTable('roles')) {
            try {
                DB::statement('ALTER TABLE `pg_opcion_menu_rol` ADD CONSTRAINT `fk_pg_opcion_menu_rol_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles`(`id`) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (Throwable $e) {
                // ignore
            }
        }

        // pg_persona_foto -> ad_archivo_digital
        if (Schema::hasTable('pg_persona_foto') && Schema::hasTable('ad_archivo_digital')) {
            try {
                DB::statement('ALTER TABLE `pg_persona_foto` ADD CONSTRAINT `pg_persona_foto_id_archivo_foreign` FOREIGN KEY (`id_archivo`) REFERENCES `ad_archivo_digital`(`id`) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (Throwable $e) {
                // ignore
            }
        }

        // email_plantillas_traduccion -> email_plantillas / pg_idiomas
        if (Schema::hasTable('email_plantillas_traduccion') && Schema::hasTable('email_plantillas')) {
            try {
                DB::statement('ALTER TABLE `email_plantillas_traduccion` ADD CONSTRAINT `email_plantillas_traduccion_email_template_id_foreign` FOREIGN KEY (`email_template_id`) REFERENCES `email_plantillas`(`id`) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (Throwable $e) {
                // ignore
            }
        }
        if (Schema::hasTable('email_plantillas_traduccion') && Schema::hasTable('pg_idiomas')) {
            try {
                DB::statement('ALTER TABLE `email_plantillas_traduccion` ADD CONSTRAINT `email_plantillas_traduccion_idioma_id_fk` FOREIGN KEY (`idioma_id`) REFERENCES `pg_idiomas`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT');
            } catch (Throwable $e) {
                // ignore
            }
        }

        // pg_usuario.id_archivo -> ad_archivo_digital
        if (Schema::hasTable('pg_usuario') && Schema::hasTable('ad_archivo_digital') && Schema::hasColumn('pg_usuario', 'id_archivo')) {
            try {
                DB::statement('ALTER TABLE `pg_usuario` ADD CONSTRAINT `fk_pg_usuario_archivo_digital` FOREIGN KEY (`id_archivo`) REFERENCES `ad_archivo_digital`(`id`) ON UPDATE CASCADE ON DELETE SET NULL');
            } catch (Throwable $e) {
                // ignore
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // No se revierte automáticamente para no romper datos.
    }
};
