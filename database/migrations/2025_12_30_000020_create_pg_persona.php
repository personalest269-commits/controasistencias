<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pg_persona')) {
            return;
        }

        // Estructura basada en el esquema proporcionado por el usuario (MySQL Workbench)
        Schema::create('pg_persona', function (Blueprint $table) {
            $table->string('id', 10)->primary();

            $table->char('tipo', 1)->default('N');
            $table->string('nombres', 255)->nullable();
            $table->string('apellido1', 20)->nullable();
            $table->string('apellido2', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->dateTime('fecha_nacimiento')->nullable();

            $table->char('tipo_identificacion', 1)->default('C');
            $table->string('identificacion', 15)->nullable();

            $table->char('sexo', 1)->nullable();
            $table->string('celular', 30)->nullable();
            $table->string('email', 50)->nullable();
            $table->char('cod_estado_civil', 1)->nullable();

            $table->dateTime('fecha_ingreso')->useCurrent();

            // null = activo, 'X' = eliminado lógico
            $table->char('estado', 1)->nullable()->default(null);

            $table->index(['estado']);
            $table->index(['identificacion']);
            $table->index(['email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_persona');
    }
};
