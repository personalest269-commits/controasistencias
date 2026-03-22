<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------
        // PG_DEPARTAMENTO
        // ---------------------------
        if (!Schema::hasTable('pg_departamento')) {
            Schema::create('pg_departamento', function (Blueprint $table) {
                $table->string('id', 10)->primary();

                // Similar a Oracle (GADSE.PG_DEPARTAMENTO)
                $table->string('codigo', 10)->nullable()->index();
                $table->string('descripcion', 255);
                $table->string('cod_padre', 10)->nullable()->index();
                $table->string('cod_programa', 10)->nullable()->index();
                $table->char('ultimo_nivel', 1)->nullable()->default('N');
                $table->date('vigencia_desde')->nullable();
                $table->date('vigencia_hasta')->nullable();
                $table->string('id_jefe', 10)->nullable()->index(); // pg_persona.id
                $table->string('identificador_activo_fijo', 2)->nullable();
                $table->string('extension_telefonica', 5)->nullable();
                $table->string('cod_clasificacion_departamento', 3)->nullable();

                // Eliminación lógica: NULL = activo, 'X' = eliminado
                $table->char('estado', 1)->nullable()->default(null)->index();

                $table->timestamps();
            });

            // FK a persona (opcional)
            if (Schema::hasTable('pg_persona')) {
                try {
                    Schema::table('pg_departamento', function (Blueprint $table) {
                        $table->foreign('id_jefe')->references('id')->on('pg_persona')
                            ->onUpdate('cascade')->onDelete('set null');
                    });
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        } else {
            // Asegurar columna estado
            if (!Schema::hasColumn('pg_departamento', 'estado')) {
                Schema::table('pg_departamento', function (Blueprint $table) {
                    $table->char('estado', 1)->nullable()->default(null)->index();
                });
            }
        }

        // ---------------------------
        // PG_EVENTOS
        // ---------------------------
        if (!Schema::hasTable('pg_eventos')) {
            Schema::create('pg_eventos', function (Blueprint $table) {
                $table->string('id', 10)->primary();

                // Guardamos también JSON (compatibilidad con tu screenshot)
                $table->longText('departamento_id')->nullable();
                $table->longText('persona_id')->nullable();

                $table->string('titulo', 191);
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->string('color', 191)->nullable();
                $table->text('descripcion')->nullable();

                $table->char('estado', 1)->nullable()->default(null)->index();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('pg_eventos', 'estado')) {
                Schema::table('pg_eventos', function (Blueprint $table) {
                    $table->char('estado', 1)->nullable()->default(null)->index();
                });
            }
        }

        // ---------------------------
        // PIVOTS (departamentos/personas por evento)
        // ---------------------------
        if (!Schema::hasTable('pg_evento_persona')) {
            Schema::create('pg_evento_persona', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('evento_id', 10)->index();
                $table->string('persona_id', 10)->index();
                $table->char('estado', 1)->nullable()->default(null)->index();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('pg_evento_persona', 'estado')) {
                Schema::table('pg_evento_persona', function (Blueprint $table) {
                    $table->char('estado', 1)->nullable()->default(null)->index();
                });
            }
        }

        if (!Schema::hasTable('pg_evento_departamento')) {
            Schema::create('pg_evento_departamento', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('evento_id', 10)->index();
                $table->string('departamento_id', 10)->index();
                $table->char('estado', 1)->nullable()->default(null)->index();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('pg_evento_departamento', 'estado')) {
                Schema::table('pg_evento_departamento', function (Blueprint $table) {
                    $table->char('estado', 1)->nullable()->default(null)->index();
                });
            }
        }

        // FKs (best effort)
        try {
            if (Schema::hasTable('pg_eventos')) {
                if (Schema::hasTable('pg_evento_persona')) {
                    Schema::table('pg_evento_persona', function (Blueprint $table) {
                        try {
                            $table->foreign('evento_id')->references('id')->on('pg_eventos')->onDelete('cascade');
                        } catch (\Throwable $e) {
                        }
                        if (Schema::hasTable('pg_persona')) {
                            try {
                                $table->foreign('persona_id')->references('id')->on('pg_persona')->onDelete('cascade');
                            } catch (\Throwable $e) {
                            }
                        }
                    });
                }

                if (Schema::hasTable('pg_evento_departamento')) {
                    Schema::table('pg_evento_departamento', function (Blueprint $table) {
                        try {
                            $table->foreign('evento_id')->references('id')->on('pg_eventos')->onDelete('cascade');
                        } catch (\Throwable $e) {
                        }
                        if (Schema::hasTable('pg_departamento')) {
                            try {
                                $table->foreign('departamento_id')->references('id')->on('pg_departamento')->onDelete('cascade');
                            } catch (\Throwable $e) {
                            }
                        }
                    });
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // ---------------------------
        // Triggers para IDs (MySQL/MariaDB) (best effort)
        // ---------------------------
        if (DB::getDriverName() === 'mysql') {
            $this->createTrigger('pg_departamento', 'PG_DEPARTAMENTO');
            $this->createTrigger('pg_eventos', 'PG_EVENTOS');
            $this->createTrigger('pg_evento_persona', 'PG_EVENTO_PERSONA');
            $this->createTrigger('pg_evento_departamento', 'PG_EVENTO_DEPARTAMENTO');
        }
    }

    private function createTrigger(string $table, string $objeto): void
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS tr_' . $table . '_bi');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::unprepared(<<<SQL
CREATE TRIGGER tr_{$table}_bi
BEFORE INSERT ON {$table}
FOR EACH ROW
BEGIN
    DECLARE v_valor BIGINT;
    IF NEW.id IS NULL OR TRIM(NEW.id) = '' THEN
        CALL sp_f_ultimo('{$objeto}', NULL, NULL, v_valor);
        SET NEW.id = LPAD(v_valor, 10, '0');
    END IF;
END
SQL);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // No se hace rollback por seguridad (pueden existir datos)
    }
};
