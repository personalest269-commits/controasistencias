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
        $badDefiner = 'mcasiste_controlasistencia@localhost';

        $rows = DB::select(
            'SELECT TRIGGER_NAME
               FROM information_schema.TRIGGERS
              WHERE TRIGGER_SCHEMA = ?
                AND EVENT_OBJECT_TABLE = ?
                AND DEFINER = ?',
            [$schema, 'pg_eventos', $badDefiner]
        );

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
