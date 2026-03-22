<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permiso adicional para controlar quién puede Aprobar/Rechazar justificaciones
        if (!Schema::hasTable('pg_permisos')) {
            return;
        }

        $name = 'pg_justificaciones_aprobaciones';
        $exists = DB::table('pg_permisos')->where('name', $name)->exists();
        if ($exists) {
            return;
        }

        $data = [
            'name' => $name,
            'display_name' => 'Justificaciones/Aprobaciones/Rechazos',
            'description' => 'Permite ver y usar los botones de Aprobar y Rechazar en Justificaciones',
        ];
        if (Schema::hasColumn('pg_permisos', 'created_at')) {
            $data['created_at'] = now();
        }
        if (Schema::hasColumn('pg_permisos', 'updated_at')) {
            $data['updated_at'] = now();
        }

        DB::table('pg_permisos')->insert($data);
    }

    public function down(): void
    {
        if (!Schema::hasTable('pg_permisos')) {
            return;
        }
        DB::table('pg_permisos')->where('name', 'pg_justificaciones_aprobaciones')->delete();
    }
};
