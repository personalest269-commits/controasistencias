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
        Schema::create('pg_persona', function (Blueprint $table) {
            $table->string('id', 10)->default('')->primary();
            $table->char('tipo', 1)->default('N');
            $table->string('nombres', 255)->nullable();
            $table->string('apellido1', 20)->nullable();
            $table->string('apellido2', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->dateTime('fecha_nacimiento')->nullable();
            $table->char('tipo_identificacion', 1)->default('C');
            $table->string('identificacion', 15)->nullable()->index();
            $table->char('sexo', 1)->nullable();
            $table->string('celular', 30)->nullable();
            $table->string('email', 50)->nullable()->index();
            $table->string('departamento_id', 10)->nullable()->index();
            $table->char('cod_estado_civil', 1)->nullable();
            $table->dateTime('fecha_ingreso')->useCurrent();
            $table->char('estado', 1)->nullable()->index();
            $table->string('vigente', 1)->nullable();
            $table->string('codigo_departamento', 45)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_persona');
    }
};
