<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo aplica para MySQL/MariaDB
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1) Tabla de control (equivalente a PG_CONTROL de Oracle)
        if (!Schema::hasTable('pg_control')) {
            Schema::create('pg_control', function (Blueprint $table) {
                $table->string('objeto', 60);
                $table->string('grupo1', 60)->default('__');
                $table->string('grupo2', 60)->default('______');
                $table->unsignedBigInteger('ultimo')->default(0);

                $table->primary(['objeto', 'grupo1', 'grupo2'], 'pk_pg_control');
            });
        }

        // 2) Procedimiento atómico (equivalente a f_ultimo)
        //    Usa LAST_INSERT_ID(expr) para devolver el valor incrementado sin colisiones.
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

        // 3) Trigger BEFORE INSERT para pg_persona.id
        DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_persona_bi');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER tr_pg_persona_bi
BEFORE INSERT ON pg_persona
FOR EACH ROW
BEGIN
    DECLARE v_valor BIGINT;

    IF NEW.id IS NULL OR TRIM(NEW.id) = '' THEN
        CALL sp_f_ultimo('PG_PERSONA', NULL, NULL, v_valor);
        SET NEW.id = LPAD(v_valor, 10, '0');
        SET @last_persona_id = NEW.id;
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_persona_bi');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_f_ultimo');

        Schema::dropIfExists('pg_control');
    }
};
