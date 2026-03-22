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
        Schema::create('fr_seccion', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('codigo', 50)->index();
            $table->integer('orden')->default(1)->index();
            $table->char('mostrar', 1)->nullable()->default('S');
            $table->string('titulo_es', 255)->nullable();
            $table->string('titulo_en', 255)->nullable();
            $table->text('subtitulo_es')->nullable();
            $table->text('subtitulo_en')->nullable();
            $table->longText('contenido_es')->nullable();
            $table->longText('contenido_en')->nullable();
            $table->string('boton_texto_es', 120)->nullable();
            $table->string('boton_texto_en', 120)->nullable();
            $table->string('boton_url', 600)->nullable();
            $table->string('clase_css', 255)->nullable();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fr_seccion');
    }
};
