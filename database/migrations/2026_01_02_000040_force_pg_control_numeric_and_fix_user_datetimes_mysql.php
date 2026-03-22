<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIX (MySQL/MariaDB)
 *
 * En instalaciones donde la BD fue creada manualmente o importada, se detectó que:
 * - pg_control.ultimo puede venir como DATETIME/VARCHAR (y entonces sp_f_ultimo falla con SQLSTATE[22007]).
 * - pg_usuario.created_at/updated_at/email_verified_at pueden venir con tipos incompatibles.
 *
 * Esta migración es "forzada" (nuevo nombre) para que corra aunque existan migraciones previas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1) Asegurar pg_control.ultimo numérico (BIGINT)
        if (Schema::hasTable('pg_control') && Schema::hasColumn('pg_control', 'ultimo')) {
            try {
                // Detectar tipo actual
                $col = DB::selectOne(
                    "SELECT DATA_TYPE AS data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pg_control' AND COLUMN_NAME = 'ultimo' LIMIT 1"
                );
                $dataType = strtolower((string) ($col->data_type ?? ''));

                $isNumeric = in_array($dataType, ['bigint', 'int', 'integer', 'mediumint', 'smallint', 'tinyint', 'decimal', 'numeric'], true);

                if (!$isNumeric) {
                    // Convertir de forma segura: crear columna nueva y reemplazar.
                    DB::statement('ALTER TABLE pg_control ADD COLUMN ultimo_new BIGINT UNSIGNED NOT NULL DEFAULT 0');

                    // Si el valor anterior era string/datetime, intentamos un cast; si no, quedará 0.
                    try {
                        DB::statement('UPDATE pg_control SET ultimo_new = IFNULL(CAST(ultimo AS UNSIGNED), 0)');
                    } catch (\Throwable $e) {
                        DB::statement('UPDATE pg_control SET ultimo_new = 0');
                    }

                    DB::statement('ALTER TABLE pg_control DROP COLUMN ultimo');
                    DB::statement('ALTER TABLE pg_control CHANGE ultimo_new ultimo BIGINT UNSIGNED NOT NULL DEFAULT 0');
                } else {
                    // Ya es numérico: solo normalizar a BIGINT
                    DB::statement('ALTER TABLE pg_control MODIFY ultimo BIGINT UNSIGNED NOT NULL DEFAULT 0');
                }
            } catch (\Throwable $e) {
                // no-op
            }
        }

        // 2) Asegurar DATETIME en pg_usuario
        if (Schema::hasTable('pg_usuario')) {
            try {
                if (Schema::hasColumn('pg_usuario', 'created_at')) {
                    DB::statement('ALTER TABLE pg_usuario MODIFY created_at DATETIME NULL');
                }
                if (Schema::hasColumn('pg_usuario', 'updated_at')) {
                    DB::statement('ALTER TABLE pg_usuario MODIFY updated_at DATETIME NULL');
                }
                if (Schema::hasColumn('pg_usuario', 'email_verified_at')) {
                    DB::statement('ALTER TABLE pg_usuario MODIFY email_verified_at DATETIME NULL');
                }
            } catch (\Throwable $e) {
                // no-op
            }
        }

        // 3) Re-sembrar contadores (evita que un cast de DATETIME genere IDs gigantes)
        try {
            // Asegurar filas base (PK: objeto,grupo1,grupo2)
            if (Schema::hasTable('pg_control')) {
                if (Schema::hasTable('pg_usuario')) {
                    DB::statement(
                        "INSERT INTO pg_control (objeto, grupo1, grupo2, ultimo) VALUES ('PG_USUARIO','__','______',(SELECT IFNULL(MAX(id),0) FROM pg_usuario))\n" .
                        "ON DUPLICATE KEY UPDATE ultimo = (SELECT IFNULL(MAX(id),0) FROM pg_usuario)"
                    );
                }

                if (Schema::hasTable('pg_persona') && Schema::hasColumn('pg_persona', 'id')) {
                    DB::statement(
                        "INSERT INTO pg_control (objeto, grupo1, grupo2, ultimo) VALUES ('PG_PERSONA','__','______',(SELECT IFNULL(MAX(CAST(id AS UNSIGNED)),0) FROM pg_persona WHERE id REGEXP '^[0-9]+$'))\n" .
                        "ON DUPLICATE KEY UPDATE ultimo = (SELECT IFNULL(MAX(CAST(id AS UNSIGNED)),0) FROM pg_persona WHERE id REGEXP '^[0-9]+$')"
                    );
                }
            }
        } catch (\Throwable $e) {
            // no-op
        }

        // 4) Re-crear procedimiento (por si existía uno viejo) - compatible MySQL/MariaDB
        try {
            DB::unprepared('DROP PROCEDURE IF EXISTS sp_f_ultimo');
            DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_f_ultimo(
    IN p_objeto VARCHAR(60),
    IN p_grupo1 VARCHAR(60),
    IN p_grupo2 VARCHAR(60),
    OUT p_new BIGINT
)
BEGIN
    DECLARE v_g1 VARCHAR(60);
    DECLARE v_g2 VARCHAR(60);

    SET v_g1 = IFNULL(NULLIF(TRIM(p_grupo1), ''), '__');
    SET v_g2 = IFNULL(NULLIF(TRIM(p_grupo2), ''), '______');

    INSERT INTO pg_control (objeto, grupo1, grupo2, ultimo)
    VALUES (p_objeto, v_g1, v_g2, LAST_INSERT_ID(1))
    ON DUPLICATE KEY UPDATE ultimo = LAST_INSERT_ID(ultimo + 1);

    SET p_new = LAST_INSERT_ID();
END
SQL);
        } catch (\Throwable $e) {
            // no-op
        }
    }

    public function down(): void
    {
        // No revertimos: es correctivo.
    }
};
