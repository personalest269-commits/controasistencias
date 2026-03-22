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
        Schema::create('pg_persona_stg', function (Blueprint $table) {
            $table->bigIncrements('stg_id');
            $table->char('batch_id', 36)->index();
            $table->string('id', 20)->nullable();
            $table->string('id_persona', 20)->nullable();
            $table->string('nombres', 255)->nullable();
            $table->string('apellido1', 20)->nullable();
            $table->string('apellido2', 20)->nullable();
            $table->char('tipo', 1)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('nombre', 255)->nullable();
            $table->string('cargo', 255)->nullable();
            $table->string('codigo_cargo', 50)->nullable();
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->char('vigente', 1)->nullable();
            $table->string('cod_departamento', 10)->nullable();
            $table->string('cod_departamento_actual', 10)->nullable();
            $table->string('codigo_departamento_padre', 10)->nullable();
            $table->string('departamento', 255)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('email_laboral', 150)->nullable();
            $table->string('id_relacion_laboral', 50)->nullable();
            $table->string('cod_motivo_accion_personal', 50)->nullable();
            $table->string('codigo_puesto', 50)->nullable();
            $table->string('codigo_puesto_jerarquia', 50)->nullable();
            $table->string('responsable', 255)->nullable();
            $table->string('identificacion', 15)->nullable()->index();
            $table->string('codigo_programa', 50)->nullable();
            $table->string('descripcion_programa', 255)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->char('tipo_identificacion', 1)->nullable();
            $table->string('descripcion_identificacion', 255)->nullable();
            $table->string('cod_estado_civil', 10)->nullable();
            $table->string('descripcion_estado_civil', 255)->nullable();
            $table->char('sexo', 1)->nullable();
            $table->string('celular', 30)->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_persona_stg');
    }
};
