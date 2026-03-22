<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL/MariaDB
 * - Cambia pg_usuario.id a VARCHAR(10)
 * - Ajusta role_user.usuario_id a VARCHAR(10)
 * - Normaliza IDs existentes con LPAD(...,10,'0')
 * - Recrea trigger tr_pg_usuario_bi para generar IDs como VARCHAR(10)
 *   usando sp_f_ultimo/pg_control y guarda @last_usuario_id.
 */
return new class extends Migration
{
    /**
     * Drop FK(s) on role_user.(usuario_id|user_id) regardless of constraint name.
     */
    private function dropRoleUserForeignKeys(): void
    {
        try {
            $rows = DB::select(
                "SELECT DISTINCT CONSTRAINT_NAME AS name\n".
                "FROM information_schema.KEY_COLUMN_USAGE\n".
                "WHERE TABLE_SCHEMA = DATABASE()\n".
                "  AND TABLE_NAME = 'role_user'\n".
                "  AND COLUMN_NAME IN ('usuario_id','user_id')\n".
                "  AND REFERENCED_TABLE_NAME IS NOT NULL"
            );

            foreach ($rows as $r) {
                if (!empty($r->name)) {
                    try {
                        DB::statement("ALTER TABLE role_user DROP FOREIGN KEY {$r->name}");
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function dropRoleUserPrimaryKey(): void
    {
        try {
            DB::statement('ALTER TABLE role_user DROP PRIMARY KEY');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        // 1) Ajustar role_user para que use usuario_id VARCHAR(10)
        if (Schema::hasTable('role_user')) {
            // 1.1) Soltar FK/PK independientemente del nombre
            $this->dropRoleUserForeignKeys();
            $this->dropRoleUserPrimaryKey();

            // 1.2) Si existe user_id pero no usuario_id, renombrar
            if (Schema::hasColumn('role_user', 'user_id') && !Schema::hasColumn('role_user', 'usuario_id')) {
                try {
                    DB::statement('ALTER TABLE role_user CHANGE user_id usuario_id BIGINT UNSIGNED NOT NULL');
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            // 1.3) Convertir tipo a VARCHAR(10)
            if (Schema::hasColumn('role_user', 'usuario_id')) {
                try {
                    DB::statement('ALTER TABLE role_user MODIFY usuario_id VARCHAR(10) NOT NULL');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        // 2) Cambiar tipo de PK en pg_usuario
        try {
            DB::statement('ALTER TABLE pg_usuario MODIFY id VARCHAR(10) NOT NULL');
        } catch (\Throwable $e) {
            // ignore
        }

        // Asegurar PK (best-effort)
        try {
            DB::statement('ALTER TABLE pg_usuario ADD PRIMARY KEY (id)');
        } catch (\Throwable $e) {
            // si ya existe, ignore
        }

        // 3) Normalizar IDs existentes a 10 dígitos (solo si son numéricos)
        try {
            DB::statement("UPDATE pg_usuario SET id = LPAD(id, 10, '0') WHERE id REGEXP '^[0-9]+$' AND CHAR_LENGTH(id) < 10");
        } catch (\Throwable $e) {
            // ignore
        }

        if (Schema::hasTable('role_user') && Schema::hasColumn('role_user', 'usuario_id')) {
            try {
                DB::statement("UPDATE role_user SET usuario_id = LPAD(usuario_id, 10, '0') WHERE usuario_id REGEXP '^[0-9]+$' AND CHAR_LENGTH(usuario_id) < 10");
            } catch (\Throwable $e) {
                // ignore
            }

            // Re-crear PK y FK
            try {
                DB::statement('ALTER TABLE role_user ADD PRIMARY KEY (usuario_id, role_id)');
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                // Asegurar un nombre estándar para el FK
                DB::statement('ALTER TABLE role_user ADD CONSTRAINT role_user_usuario_id_foreign FOREIGN KEY (usuario_id) REFERENCES pg_usuario(id) ON UPDATE CASCADE ON DELETE CASCADE');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 4) Sincronizar pg_control para PG_USUARIO con el MAX(id) numérico actual
        try {
            DB::statement(
                "INSERT INTO pg_control (objeto, grupo1, grupo2, ultimo)\n" .
                "VALUES ('PG_USUARIO','__','______', (SELECT IFNULL(MAX(CAST(id AS UNSIGNED)),0) FROM pg_usuario))\n" .
                "ON DUPLICATE KEY UPDATE ultimo = GREATEST(ultimo, VALUES(ultimo))"
            );
        } catch (\Throwable $e) {
            // ignore
        }

        // 5) Re-crear trigger de pg_usuario para generar id VARCHAR(10)
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_usuario_bi');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::unprepared(
                "CREATE TRIGGER tr_pg_usuario_bi BEFORE INSERT ON pg_usuario\n" .
                "FOR EACH ROW\n" .
                "BEGIN\n" .
                "  DECLARE v_valor BIGINT;\n" .
                "  IF NEW.id IS NULL OR TRIM(NEW.id) = '' THEN\n" .
                "    CALL sp_f_ultimo('PG_USUARIO', NULL, NULL, v_valor);\n" .
                "    SET NEW.id = LPAD(v_valor, 10, '0');\n" .
                "    SET @last_usuario_id = NEW.id;\n" .
                "  END IF;\n" .
                "END"
            );
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Best-effort rollback: dejamos el trigger y el tipo (evita romper datos).
        // Si necesitas revertir a BIGINT, sería una migración separada con conversión.
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_usuario_bi');
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
