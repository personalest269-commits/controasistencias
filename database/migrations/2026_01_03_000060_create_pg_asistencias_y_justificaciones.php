<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asistencia por evento (por persona)
        if (!Schema::hasTable('pg_asistencia_evento')) {
            Schema::create('pg_asistencia_evento', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('evento_id', 10);
                $table->string('persona_id', 10);
                $table->date('fecha');
                // Evidencia individual (1 foto por persona) -> ad_archivo_digital
                $table->string('id_archivo', 10)->nullable();
                // Evidencia por departamento (lote) -> pg_asistencia_lote
                $table->string('asistencia_lote_id', 10)->nullable();

                $table->string('estado_asistencia', 1)->nullable(); // A=Asistió, T=Tarde (opcional)
                $table->text('observacion')->nullable();

                $table->string('estado', 1)->nullable(); // NULL activo, X eliminado lógico
                $table->timestamps();

                $table->index(['evento_id', 'fecha']);
                $table->index(['persona_id', 'fecha']);
            });

            // Unicidad solo para registros activos (estado IS NULL)
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS ux_pg_asistencia_evento_activo ON pg_asistencia_evento(evento_id, persona_id, fecha) WHERE estado IS NULL");
            } else {
                Schema::table('pg_asistencia_evento', function (Blueprint $table) {
                    $table->unique(['evento_id', 'persona_id', 'fecha'], 'ux_pg_asistencia_evento_activo');
                });
            }
        }

        // Lote de asistencia por departamento (una vez por evento)
        if (!Schema::hasTable('pg_asistencia_lote')) {
            Schema::create('pg_asistencia_lote', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('evento_id', 10);
                $table->string('departamento_id', 10);
                $table->date('fecha');
                $table->text('observacion')->nullable();
                $table->string('estado', 1)->nullable();
                $table->timestamps();

                $table->index(['evento_id', 'fecha']);
                $table->index(['departamento_id', 'fecha']);
            });

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS ux_pg_asistencia_lote_activo ON pg_asistencia_lote(evento_id, departamento_id, fecha) WHERE estado IS NULL");
            } else {
                Schema::table('pg_asistencia_lote', function (Blueprint $table) {
                    $table->unique(['evento_id', 'departamento_id', 'fecha'], 'ux_pg_asistencia_lote_activo');
                });
            }
        }

        // Archivos asociados al lote (máx 4 por evento/departamento/día - validación en backend)
        if (!Schema::hasTable('pg_asistencia_lote_archivo')) {
            Schema::create('pg_asistencia_lote_archivo', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('asistencia_lote_id', 10);
                $table->string('id_archivo', 10);
                $table->string('estado', 1)->nullable();
                $table->timestamps();

                $table->index('asistencia_lote_id');
                $table->index('id_archivo');
            });
        }

        // Justificación de asistencia (por persona/evento/fecha)
        if (!Schema::hasTable('pg_justificacion_asistencia')) {
            Schema::create('pg_justificacion_asistencia', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('evento_id', 10);
                $table->string('persona_id', 10);
                $table->date('fecha');
                $table->text('motivo');

                // P=Pendiente, A=Aprobada, R=Rechazada
                $table->string('estado_revision', 1)->default('P');
                $table->string('revisado_por', 10)->nullable();
                $table->timestamp('revisado_en')->nullable();

                // Archivo principal (opcional)
                $table->string('id_archivo', 10)->nullable();

                $table->string('estado', 1)->nullable();
                $table->timestamps();

                $table->index(['persona_id', 'fecha']);
                $table->index(['evento_id', 'fecha']);
            });

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS ux_pg_justificacion_asistencia_activo ON pg_justificacion_asistencia(evento_id, persona_id, fecha) WHERE estado IS NULL");
            } else {
                Schema::table('pg_justificacion_asistencia', function (Blueprint $table) {
                    $table->unique(['evento_id', 'persona_id', 'fecha'], 'ux_pg_justificacion_asistencia_activo');
                });
            }
        }

        // Archivos múltiples para justificación
        if (!Schema::hasTable('pg_justificacion_asistencia_archivo')) {
            Schema::create('pg_justificacion_asistencia_archivo', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('justificacion_id', 10);
                $table->string('id_archivo', 10);
                $table->string('estado', 1)->nullable();
                $table->timestamps();

                $table->index('justificacion_id');
                $table->index('id_archivo');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_justificacion_asistencia_archivo');
        Schema::dropIfExists('pg_justificacion_asistencia');
        Schema::dropIfExists('pg_asistencia_lote_archivo');
        Schema::dropIfExists('pg_asistencia_lote');
        Schema::dropIfExists('pg_asistencia_evento');
    }
};
