<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_eventos')) {
            return;
        }

        // Cambiar DATE -> DATETIME sin depender de doctrine/dbal
        DB::statement("ALTER TABLE pg_eventos MODIFY fecha_inicio DATETIME NOT NULL");
        DB::statement("ALTER TABLE pg_eventos MODIFY fecha_fin DATETIME NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('pg_eventos')) {
            return;
        }

        DB::statement("ALTER TABLE pg_eventos MODIFY fecha_inicio DATE NOT NULL");
        DB::statement("ALTER TABLE pg_eventos MODIFY fecha_fin DATE NOT NULL");
    }
};
