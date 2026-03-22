<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reparación final (MySQL/MariaDB)
 *
 * - Fuerza pg_control.ultimo a BIGINT incluso si fue importado como DATETIME/TIMESTAMP/VARCHAR.
 * - Re-crea el procedimiento sp_f_ultimo.
 * - Re-semilla el contador de PG_USUARIO y re-crea el trigger tr_pg_usuario_bi (id VARCHAR(10)).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1) Arreglar pg_control.ultimo
        if (Schema::hasTable('pg_control') && Schema::hasColumn('pg_control', 'ultimo')) {
            try {
                $col = DB::selectOne(
                    "SELECT DATA_TYPE AS data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pg_control' AND COLUMN_NAME = 'ultimo' LIMIT 1"
                );
                $dataType = strtolower((string) ($col->data_type ?? ''));
                $isNumeric = in_array($dataType, ['bigint','int','integer','mediumint','smallint','tinyint','decimal','numeric'], true);

                if (!$isNumeric) {
                    // Crear columna nueva y reemplazar para evitar fallos al convertir DATETIME/TIMESTAMP.
                    DB::statement('ALTER TABLE pg_control ADD COLUMN ultimo_new BIGINT UNSIGNED NOT NULL DEFAULT 0');

                    // Si el valor tiene dígitos al inicio, CAST lo toma; si no, queda 0.
                    try {
                        DB::statement("UPDATE pg_control SET ultimo_new = IFNULL(CAST(ultimo AS UNSIGNED), 0)");
                    } catch (\Throwable $e) {
                        DB::statement('UPDATE pg_control SET ultimo_new = 0');
                    }

                    DB::statement('ALTER TABLE pg_control DROP COLUMN ultimo');
                    DB::statement('ALTER TABLE pg_control CHANGE ultimo_new ultimo BIGINT UNSIGNED NOT NULL DEFAULT 0');
                } else {
                    DB::statement('ALTER TABLE pg_control MODIFY ultimo BIGINT UNSIGNED NOT NULL DEFAULT 0');
                }
            } catch (\Throwable $e) {
                // no-op
            }
        }

        // 2) Re-crear procedimiento sp_f_ultimo
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

        // 3) Re-semilla contador para PG_USUARIO basado en MAX(id)
        try {
            if (Schema::hasTable('pg_control') && Schema::hasTable('pg_usuario')) {
                DB::statement(
                    "INSERT INTO pg_control (objeto, grupo1, grupo2, ultimo) VALUES ('PG_USUARIO','__','______',(SELECT IFNULL(MAX(CAST(id AS UNSIGNED)),0) FROM pg_usuario))\n" .
                    "ON DUPLICATE KEY UPDATE ultimo = GREATEST(ultimo, VALUES(ultimo))"
                );
            }
        } catch (\Throwable $e) {
            // no-op
        }

        // 4) Re-crear trigger tr_pg_usuario_bi (id VARCHAR(10) + @last_usuario_id)
        try {
            if (Schema::hasTable('pg_usuario') && Schema::hasColumn('pg_usuario', 'id')) {
                DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_usuario_bi');
                DB::unprepared(<<<'SQL'
CREATE TRIGGER tr_pg_usuario_bi BEFORE INSERT ON pg_usuario
FOR EACH ROW
BEGIN
  DECLARE v_valor BIGINT;
  IF NEW.id IS NULL OR TRIM(NEW.id) = '' THEN
    CALL sp_f_ultimo('PG_USUARIO', NULL, NULL, v_valor);
    SET NEW.id = LPAD(v_valor, 10, '0');
    SET @last_usuario_id = NEW.id;
  END IF;
END
SQL);
            }
        } catch (\Throwable $e) {
            // no-op
        }
    }

    public function down(): void
    {
        // correctivo, no se revierte.
    }
};
