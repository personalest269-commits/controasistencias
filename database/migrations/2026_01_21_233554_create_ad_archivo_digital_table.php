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
        Schema::create('ad_archivo_digital', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('tipo_documento_codigo', 5)->nullable()->index();
            $table->string('tipo_archivo_codigo', 5)->nullable()->index();
            $table->string('nombre_original', 255);
            $table->string('ruta', 600);
            $table->longText('digital')->nullable();
            $table->string('tipo_mime', 255);
            $table->string('extension', 10);
            $table->integer('tamano');
            $table->string('descripcion', 255)->nullable();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_archivo_digital');
    }
};
