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
        Schema::create('pg_departamento', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('codigo', 10)->nullable()->index();
            $table->string('descripcion', 255);
            $table->string('cod_padre', 10)->nullable()->index();
            $table->string('cod_programa', 10)->nullable()->index();
            $table->char('ultimo_nivel', 1)->nullable()->default('N');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->string('id_jefe', 10)->nullable()->index();
            $table->string('identificador_activo_fijo', 2)->nullable();
            $table->string('extension_telefonica', 5)->nullable();
            $table->string('cod_clasificacion_departamento', 3)->nullable();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_departamento');
    }
};
