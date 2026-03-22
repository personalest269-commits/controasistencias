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
        Schema::create('fr_pagina_inicio', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('nombre_sitio_es', 200)->nullable();
            $table->string('nombre_sitio_en', 200)->nullable();
            $table->string('logo_archivo_id', 20)->nullable()->index();
            $table->string('hero_titulo_es', 255)->nullable();
            $table->string('hero_titulo_en', 255)->nullable();
            $table->text('hero_subtitulo_es')->nullable();
            $table->text('hero_subtitulo_en')->nullable();
            $table->string('hero_boton_texto_es', 120)->nullable();
            $table->string('hero_boton_texto_en', 120)->nullable();
            $table->string('hero_boton_url', 600)->nullable();
            $table->string('hero_fondo_archivo_id', 20)->nullable()->index();
            $table->string('contacto_telefono', 120)->nullable();
            $table->string('contacto_email', 255)->nullable();
            $table->string('contacto_direccion_es', 255)->nullable();
            $table->string('contacto_direccion_en', 255)->nullable();
            $table->char('cookies_activo', 1)->nullable()->default('N');
            $table->text('cookies_texto_es')->nullable();
            $table->text('cookies_texto_en')->nullable();
            $table->string('cookies_btn_aceptar_es', 80)->nullable();
            $table->string('cookies_btn_aceptar_en', 80)->nullable();
            $table->string('cookies_btn_rechazar_es', 80)->nullable();
            $table->string('cookies_btn_rechazar_en', 80)->nullable();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fr_pagina_inicio');
    }
};
