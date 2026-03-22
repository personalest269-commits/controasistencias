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
        Schema::create('pg_justificacion_asistencia', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('evento_id', 10);
            $table->string('persona_id', 10);
            $table->date('fecha');
            $table->text('motivo');
            $table->string('estado_revision', 1)->default('P');
            $table->string('revisado_por', 10)->nullable();
            $table->timestamp('revisado_en')->nullable();
            $table->string('id_archivo', 10)->nullable();
            $table->string('estado', 1)->nullable();
            $table->timestamps();

            $table->index(['evento_id', 'fecha']);
            $table->index(['persona_id', 'fecha']);
            $table->unique(['evento_id', 'persona_id', 'fecha'], 'ux_pg_justificacion_asistencia_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_justificacion_asistencia');
    }
};
