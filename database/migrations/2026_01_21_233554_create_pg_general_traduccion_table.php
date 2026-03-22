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
        Schema::create('pg_general_traduccion', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('clave', 255);
            // Relación directa al catálogo de idiomas
            $table->string('id_dioma', 10)->nullable()->index('idx_pg_general_traduccion_id_dioma');
            $table->string('idioma_codigo', 10)->index('idx_pg_general_traduccion_idioma');
            $table->text('texto');
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();

            $table->unique(['clave', 'idioma_codigo'], 'uq_pg_general_traduccion_clave_idioma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_general_traduccion');
    }
};
