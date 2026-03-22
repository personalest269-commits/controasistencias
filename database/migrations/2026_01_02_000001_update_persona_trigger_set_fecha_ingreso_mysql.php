<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Solo aplica para MySQL/MariaDB
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Re-crear el trigger para también setear fecha_ingreso (solo fecha: día/mes/año)
        // Nota: CURDATE() en DATETIME queda como 00:00:00.
        DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_persona_bi');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER tr_pg_persona_bi
BEFORE INSERT ON pg_persona
FOR EACH ROW
BEGIN
    DECLARE v_valor BIGINT;

    -- ID por control (0000000001...)
    IF NEW.id IS NULL OR TRIM(NEW.id) = '' THEN
        CALL sp_f_ultimo('PG_PERSONA', NULL, NULL, v_valor);
        SET NEW.id = LPAD(v_valor, 10, '0');
        SET @last_persona_id = NEW.id;
    ELSE
        SET @last_persona_id = NEW.id;
    END IF;

    -- Fecha ingreso (solo día/mes/año)
    IF NEW.fecha_ingreso IS NULL THEN
        SET NEW.fecha_ingreso = CURDATE();
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Volver a la versión anterior del trigger (sin fecha_ingreso explícita)
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
};
