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

        // Evita error 1449 por DEFINER inexistente en ambientes restaurados desde dump.
        DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_asistencia_evento_bi');
    }

    public function down(): void
    {
        // No se recrea trigger: el ID se genera por aplicación (trait GeneraIdVarchar).
    }
};
