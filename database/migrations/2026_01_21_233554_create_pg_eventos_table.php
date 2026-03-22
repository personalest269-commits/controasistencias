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
        Schema::create('pg_eventos', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->longText('departamento_id')->nullable();
            $table->longText('persona_id')->nullable();
            $table->string('titulo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('color')->nullable();
            $table->text('descripcion')->nullable();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_eventos');
    }
};
