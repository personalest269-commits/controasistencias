<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Se elimina índice/unique por evento porque ahora evento_id almacena lista compacta.
        try {
            DB::statement('ALTER TABLE `pg_asistencia_evento` DROP INDEX `ux_pg_asistencia_evento_activo`');
        } catch (\Throwable $e) {
            // índice ya no existe
        }

        try {
            DB::statement('ALTER TABLE `pg_asistencia_evento` DROP INDEX `pg_asistencia_evento_evento_id_fecha_index`');
        } catch (\Throwable $e) {
            // índice ya no existe
        }

        DB::statement('ALTER TABLE `pg_asistencia_evento` MODIFY `evento_id` TEXT NULL');
        DB::statement('ALTER TABLE `pg_asistencia_evento` MODIFY `id_archivo` TEXT NULL');
        DB::statement('ALTER TABLE `pg_asistencia_evento` MODIFY `estado_asistencia` TEXT NULL');

        // Mantener índice de búsqueda principal por persona/fecha.
        try {
            DB::statement('ALTER TABLE `pg_asistencia_evento` ADD INDEX `pg_asistencia_evento_persona_id_fecha_index` (`persona_id`, `fecha`)');
        } catch (\Throwable $e) {
            // índice existente
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `pg_asistencia_evento` MODIFY `evento_id` VARCHAR(10) NOT NULL');
        DB::statement('ALTER TABLE `pg_asistencia_evento` MODIFY `id_archivo` VARCHAR(10) NULL');
        DB::statement('ALTER TABLE `pg_asistencia_evento` MODIFY `estado_asistencia` VARCHAR(1) NULL');

        try {
            DB::statement('ALTER TABLE `pg_asistencia_evento` ADD INDEX `pg_asistencia_evento_evento_id_fecha_index` (`evento_id`(10), `fecha`)');
        } catch (\Throwable $e) {
            // índice existente
        }

        try {
            DB::statement('ALTER TABLE `pg_asistencia_evento` ADD UNIQUE `ux_pg_asistencia_evento_activo` (`evento_id`(10), `persona_id`, `fecha`)');
        } catch (\Throwable $e) {
            // unique existente
        }
    }
};
