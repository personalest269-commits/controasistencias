<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_control')) {
            return;
        }

        $objects = [
            'PG_ASISTENCIA_EVENTO',
            'PG_ASISTENCIA_LOTE',
            'PG_ASISTENCIA_LOTE_ARCHIVO',
            'PG_JUSTIFICACION_ASISTENCIA',
            'PG_JUSTIFICACION_ASISTENCIA_ARCHIVO',
        ];

        foreach ($objects as $obj) {
            try {
                // Intento genérico: insertar si no existe
                $exists = DB::table('pg_control')
                    ->where('objeto', $obj)
                    ->where('grupo1', '__')
                    ->where('grupo2', '______')
                    ->exists();

                if (!$exists) {
                    DB::table('pg_control')->insert([
                        'objeto' => $obj,
                        'grupo1' => '__',
                        'grupo2' => '______',
                        'ultimo' => 0,
                    ]);
                }
            } catch (Throwable $e) {
                // No bloqueamos instalaciones donde pg_control tenga otra PK o tipos.
            }
        }
    }

    public function down(): void
    {
        // No eliminamos para evitar afectar instalaciones existentes.
    }
};
