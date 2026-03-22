<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        // Solo aplica para MySQL/MariaDB
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Asegurar que exista el registro de control (para evitar colisiones con IDs existentes)
        // NOTA: grupo1/grupo2 se guardan como NULL en la app, pero la PK usa NVL-equivalente ('__' / '______')
        try {
            DB::statement(
                "INSERT INTO pg_control (objeto, grupo1, grupo2, ultimo)\n" .
                "VALUES ('PG_USUARIO', '__', '______', (SELECT IFNULL(MAX(id),0) FROM pg_usuario))\n" .
                "ON DUPLICATE KEY UPDATE ultimo = GREATEST(ultimo, VALUES(ultimo))"
            );
        } catch (\Throwable $e) {
            // Si pg_control no existe aún o la PK es distinta, no bloqueamos.
        }

        // Crear trigger BEFORE INSERT para asignar ID usando pg_control/sp_f_ultimo
        DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_usuario_bi');

        DB::unprepared(
            "CREATE TRIGGER tr_pg_usuario_bi BEFORE INSERT ON pg_usuario\n" .
            "FOR EACH ROW\n" .
            "BEGIN\n" .
            "  DECLARE v_valor BIGINT;\n" .
            "  IF NEW.id IS NULL OR NEW.id = 0 THEN\n" .
            "    CALL sp_f_ultimo('PG_USUARIO', NULL, NULL, v_valor);\n" .
            "    SET NEW.id = v_valor;\n" .
            "  END IF;\n" .
            "END"
        );

        // Evitar duplicados: 1 usuario por persona (MySQL permite múltiples NULL en unique)
        try {
            Schema::table('pg_usuario', function (Blueprint $table) {
                $table->unique('id_persona', 'uk_pg_usuario_id_persona');
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_usuario_bi');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            Schema::table('pg_usuario', function (Blueprint $table) {
                $table->dropUnique('uk_pg_usuario_id_persona');
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
