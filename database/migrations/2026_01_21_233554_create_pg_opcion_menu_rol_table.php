<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pg_opcion_menu_rol', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('id_opcion_menu', 10);
            $table->string('id_rol', 10)->nullable()->index('idx_pg_opcion_menu_rol_id_rol');
            $table->char('estado', 1)->nullable()->index();

            $table->unique(['id_opcion_menu', 'id_rol'], 'uq_pg_opcion_menu_rol_opcion_rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_opcion_menu_rol');
    }
};
