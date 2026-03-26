<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('pg_configuraciones')->updateOrInsert(
            ['clave' => 'ASISTENCIA_MODO_REGISTRO'],
            [
                'valor' => 'single_check',
                'tipo' => 'texto',
                'descripcion' => 'Modo de validación de asistencia: single_check (1 check) o dual_check (inicio+fin).',
                'grupo' => 'asistencias',
                'activo' => 'S',
                'estado' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('pg_configuraciones')
            ->where('clave', 'ASISTENCIA_MODO_REGISTRO')
            ->delete();
    }
};
