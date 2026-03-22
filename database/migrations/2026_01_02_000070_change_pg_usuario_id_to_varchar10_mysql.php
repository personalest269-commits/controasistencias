<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL/MariaDB
 *
 * Cambia pg_usuario.id a VARCHAR(10) (padded) y ajusta role_user.usuario_id.
 * Esto asegura que el ID generado por trigger quede consistente (ej: 0000000001)
 * y que la FK/PK del pivot role_user sea compatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        // Desactivar FK checks para poder modificar PK/FK sin errores.
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // 1) Drop FK/PK del pivot (si existe)
        if (Schema::hasTable('role_user')) {
            try { DB::statement('ALTER TABLE role_user DROP FOREIGN KEY role_user_usuario_id_foreign'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE role_user DROP PRIMARY KEY'); } catch (\Throwable $e) {}
        }

        // 2) Quitar AUTO_INCREMENT y luego cambiar tipo a VARCHAR(10)
        //    (MySQL no permite AUTO_INCREMENT en VARCHAR)
        try {
            DB::statement('ALTER TABLE pg_usuario MODIFY id BIGINT UNSIGNED NOT NULL');
        } catch (\Throwable $e) {
            // noop
        }

        try {
            DB::statement('ALTER TABLE pg_usuario MODIFY id VARCHAR(10) NOT NULL');
        } catch (\Throwable $e) {
            // En algunos casos (según versión/engine), puede fallar.
            // Intentamos fallback con CHANGE.
            try {
                DB::statement('ALTER TABLE pg_usuario CHANGE id id VARCHAR(10) NOT NULL');
            } catch (\Throwable $e2) {
                // noop
            }
        }

        // 3) Pad de IDs existentes
        try {
            DB::statement("UPDATE pg_usuario SET id = LPAD(TRIM(id), 10, '0') WHERE id REGEXP '^[0-9]+$' AND LENGTH(TRIM(id)) < 10");
        } catch (\Throwable $e) {}

        // 4) Ajustar role_user.usuario_id a VARCHAR(10)
        if (Schema::hasTable('role_user') && Schema::hasColumn('role_user', 'usuario_id')) {
            try {
                DB::statement('ALTER TABLE role_user MODIFY usuario_id VARCHAR(10) NOT NULL');
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE role_user CHANGE usuario_id usuario_id VARCHAR(10) NOT NULL');
                } catch (\Throwable $e2) {}
            }

            try {
                DB::statement("UPDATE role_user SET usuario_id = LPAD(TRIM(usuario_id), 10, '0') WHERE usuario_id REGEXP '^[0-9]+$' AND LENGTH(TRIM(usuario_id)) < 10");
            } catch (\Throwable $e) {}

            // Re-crear PK compuesta
            try {
                DB::statement('ALTER TABLE role_user ADD PRIMARY KEY (usuario_id, role_id)');
            } catch (\Throwable $e) {}

            // Re-crear FK hacia pg_usuario
            try {
                DB::statement('ALTER TABLE role_user ADD CONSTRAINT role_user_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES pg_usuario(id) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (\Throwable $e) {}
        }

        // 5) Re-seed de pg_control para PG_USUARIO usando CAST, evitando problemas
        //    si antes se guardó un valor inválido.
        if (Schema::hasTable('pg_control')) {
            try {
                DB::statement("INSERT INTO pg_control (objeto, grupo1, grupo2, ultimo) VALUES ('PG_USUARIO','__','______',(SELECT IFNULL(MAX(CAST(id AS UNSIGNED)),0) FROM pg_usuario WHERE id REGEXP '^[0-9]+$')) ON DUPLICATE KEY UPDATE ultimo=(SELECT IFNULL(MAX(CAST(id AS UNSIGNED)),0) FROM pg_usuario WHERE id REGEXP '^[0-9]+$')");
            } catch (\Throwable $e) {}
        }

        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // No se recomienda revertir este cambio en producción.
    }
};
