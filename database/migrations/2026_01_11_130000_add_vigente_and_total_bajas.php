<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Asegurar columna vigente en pg_persona
        if (Schema::hasTable('pg_persona') && !Schema::hasColumn('pg_persona', 'vigente')) {
            Schema::table('pg_persona', function (Blueprint $table) {
                $table->char('vigente', 1)->default('S')->after('estado');
            });
        }

        // Agregar total_bajas al encabezado de importación
        if (Schema::hasTable('pg_importacion_batches') && !Schema::hasColumn('pg_importacion_batches', 'total_bajas')) {
            Schema::table('pg_importacion_batches', function (Blueprint $table) {
                $table->integer('total_bajas')->default(0)->after('total_update');
            });
        }
    }

    public function down(): void
    {
        // no-op
    }
};
