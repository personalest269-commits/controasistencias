<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $schema = DB::getDatabaseName();
        $targets = ['pg_eventos', 'pg_evento_persona', 'pg_evento_departamento', 'pg_asistencia_evento'];

        $placeholders = implode(',', array_fill(0, count($targets), '?'));
        $params = array_merge([$schema], $targets);

        $sql = "SELECT TRIGGER_NAME
                  FROM information_schema.TRIGGERS
                 WHERE TRIGGER_SCHEMA = ?
                   AND EVENT_OBJECT_TABLE IN ({$placeholders})
                   AND (DEFINER = 'mcasiste_controlasistencia@localhost' OR DEFINER LIKE 'mcasiste_controlasistencia@%')";

        $rows = DB::select($sql, $params);

        foreach ($rows as $r) {
            $name = (string) ($r->TRIGGER_NAME ?? '');
            if ($name === '') {
                continue;
            }
            $safe = str_replace('`', '``', $name);
            DB::unprepared("DROP TRIGGER IF EXISTS `{$safe}`");
        }
    }

    public function down(): void
    {
        // No-op: no recreamos triggers con definer inválido.
    }
};
