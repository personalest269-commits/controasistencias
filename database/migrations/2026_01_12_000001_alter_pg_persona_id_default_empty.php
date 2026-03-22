<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // En algunos entornos con sql_mode STRICT, un INSERT que omite un campo NOT NULL sin DEFAULT
        // puede fallar antes de ejecutar triggers. Este DEFAULT '' permite que el trigger tr_pg_persona_bi
        // reemplace el valor con el correlativo (LPAD...) sin provocar el error 1364.
        DB::statement("ALTER TABLE pg_persona MODIFY id VARCHAR(10) NOT NULL DEFAULT ''");
    }

    public function down(): void
    {
        // Mantener compatibilidad: no revertimos el DEFAULT para evitar romper inserciones existentes.
        // Si necesitas revertirlo, ajusta manualmente según tu esquema.
    }
};
