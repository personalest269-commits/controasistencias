<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pg_persona_stg', function (Blueprint $table) {

            // Campos del Excel/API que el INSERT está usando y pueden faltar
            if (!Schema::hasColumn('pg_persona_stg', 'cargo')) $table->string('cargo', 255)->nullable();
            if (!Schema::hasColumn('pg_persona_stg', 'codigo_cargo')) $table->string('codigo_cargo', 50)->nullable();

            if (!Schema::hasColumn('pg_persona_stg', 'vigencia_desde')) $table->date('vigencia_desde')->nullable();
            if (!Schema::hasColumn('pg_persona_stg', 'vigencia_hasta')) $table->date('vigencia_hasta')->nullable();

            if (!Schema::hasColumn('pg_persona_stg', 'departamento')) $table->string('departamento', 255)->nullable();
            if (!Schema::hasColumn('pg_persona_stg', 'nombre')) $table->string('nombre', 255)->nullable();
            if (!Schema::hasColumn('pg_persona_stg', 'responsable')) $table->string('responsable', 255)->nullable();

            if (!Schema::hasColumn('pg_persona_stg', 'id_relacion_laboral')) $table->string('id_relacion_laboral', 50)->nullable();
            if (!Schema::hasColumn('pg_persona_stg', 'cod_motivo_accion_personal')) $table->string('cod_motivo_accion_personal', 50)->nullable();

            if (!Schema::hasColumn('pg_persona_stg', 'cod_departamento_actual')) $table->string('cod_departamento_actual', 20)->nullable();
            if (!Schema::hasColumn('pg_persona_stg', 'codigo_departamento_padre')) $table->string('codigo_departamento_padre', 20)->nullable();

            if (!Schema::hasColumn('pg_persona_stg', 'codigo_puesto')) $table->string('codigo_puesto', 50)->nullable();
            if (!Schema::hasColumn('pg_persona_stg', 'codigo_puesto_jerarquia')) $table->string('codigo_puesto_jerarquia', 50)->nullable();

            if (!Schema::hasColumn('pg_persona_stg', 'codigo_programa')) $table->string('codigo_programa', 50)->nullable();
            if (!Schema::hasColumn('pg_persona_stg', 'descripcion_programa')) $table->string('descripcion_programa', 255)->nullable();

            if (!Schema::hasColumn('pg_persona_stg', 'descripcion_identificacion')) $table->string('descripcion_identificacion', 255)->nullable();

            if (!Schema::hasColumn('pg_persona_stg', 'fecha_ingreso')) $table->date('fecha_ingreso')->nullable();
        });
    }

    public function down(): void
    {
        // no-op
    }
};
