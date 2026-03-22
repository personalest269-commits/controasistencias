<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multilenguaje (ES/EN) sin API externa.
 *
 * - Normaliza IDs de la tabla `idiomas` a VARCHAR(10) tipo 0000000001.
 * - Crea desde cero `pg_general_traduccion` (si existe la elimina y la recrea).
 *
 * IMPORTANTE:
 * - Este sistema usa `idiomas.codigo` (es/en) como referencia para traducciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Normalizar idiomas (si existe)
        if (Schema::hasTable('pg_idiomas')) {
            $this->normalizeIdiomas();
        }

        // 2) Re-crear tabla de traducciones
        if (Schema::hasTable('pg_general_traduccion')) {
            Schema::drop('pg_general_traduccion');
        }

        Schema::create('pg_general_traduccion', function (Blueprint $table) {
            // IDs como string 10 dígitos (0000000001)
            $table->string('id', 10)->primary();
            $table->string('clave', 255);
            // Relación directa al catálogo de idiomas
            $table->string('id_dioma', 10)->nullable()->index('idx_pg_general_traduccion_id_dioma');
            // Referencia por código (es/en) para evitar problemas al cambiar IDs
            $table->string('idioma_codigo', 10);
            $table->text('texto');
            // Eliminación lógica: NULL = activo, 'X' = eliminado
            $table->char('estado', 1)->nullable()->default(null)->index();
            $table->timestamps();

            $table->unique(['clave', 'idioma_codigo'], 'uq_pg_general_traduccion_clave_idioma');
            $table->index(['idioma_codigo'], 'idx_pg_general_traduccion_idioma');
        });

        // FK a pg_idiomas (por codigo e id)
        try {
            if (Schema::hasTable('pg_idiomas') && Schema::hasColumn('pg_idiomas', 'codigo')) {
                Schema::table('pg_general_traduccion', function (Blueprint $table) {
                    $table->foreign('idioma_codigo', 'fk_pg_general_traduccion_idiomas_codigo')
                        ->references('codigo')->on('pg_idiomas')
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                    $table->foreign('id_dioma', 'fk_pg_general_traduccion_pg_idiomas_id')
                        ->references('id')->on('pg_idiomas')
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                });
            }
        } catch (Throwable $e) {
            // En algunos entornos el motor puede no soportar la FK como está; no rompemos.
        }

        // Seed mínimo (asegurar ES/EN si no están)
        $this->seedIdiomasIfMissing();
    }

    private function seedIdiomasIfMissing(): void
    {
        if (!Schema::hasTable('pg_idiomas')) {
            return;
        }
        $cols = Schema::getColumnListing('pg_idiomas');
        $hasId = in_array('id', $cols, true);
        $hasCodigo = in_array('codigo', $cols, true);
        $hasNombre = in_array('nombre', $cols, true);
        if (!$hasCodigo || !$hasNombre) {
            return;
        }

        $now = now();

        $existsEs = DB::table('pg_idiomas')->where('codigo', 'es')->exists();
        $existsEn = DB::table('pg_idiomas')->where('codigo', 'en')->exists();

        if (!$existsEs) {
            $data = [
                'codigo' => 'es',
                'nombre' => 'Español',
            ];
            if (in_array('activo', $cols, true)) $data['activo'] = 1;
            if (in_array('por_defecto', $cols, true)) $data['por_defecto'] = 1;
            if (in_array('created_at', $cols, true)) $data['created_at'] = $now;
            if (in_array('updated_at', $cols, true)) $data['updated_at'] = $now;
            if ($hasId) $data['id'] = '0000000001';
            DB::table('pg_idiomas')->insert($data);
        }

        if (!$existsEn) {
            $data = [
                'codigo' => 'en',
                'nombre' => 'English',
            ];
            if (in_array('activo', $cols, true)) $data['activo'] = 1;
            if (in_array('por_defecto', $cols, true)) $data['por_defecto'] = 0;
            if (in_array('created_at', $cols, true)) $data['created_at'] = $now;
            if (in_array('updated_at', $cols, true)) $data['updated_at'] = $now;
            if ($hasId) $data['id'] = '0000000002';
            DB::table('pg_idiomas')->insert($data);
        }
    }

    /**
     * Normaliza IDs en `pg_idiomas` a 10 dígitos. Respeta códigos:
     * - es => 0000000001
     * - en => 0000000002
     */
    private function normalizeIdiomas(): void
    {
        $cols = Schema::getColumnListing('pg_idiomas');
        if (!in_array('id', $cols, true) || !in_array('codigo', $cols, true)) {
            return;
        }

        // Asegurar que existan filas base.
        $this->seedIdiomasIfMissing();

        $rows = DB::table('pg_idiomas')->select('id', 'codigo')->whereIn('codigo', ['es', 'en'])->get();
        foreach ($rows as $r) {
            $codigo = (string) $r->codigo;
            $oldId = (string) $r->id;
            $newId = $codigo === 'es' ? '0000000001' : '0000000002';
            if ($oldId === $newId) {
                continue;
            }

            // Actualizar PK (si hay FK ON UPDATE CASCADE, se propagará).
            try {
                DB::table('pg_idiomas')->where('codigo', $codigo)->update(['id' => $newId]);
            } catch (Throwable $e) {
                // Si no permite update de PK, no rompemos instalación.
            }

            // Reconciliar tablas que referencian idiomas por idioma_id (si existen y NO tienen cascade).
            // email_plantillas_traduccion.idioma_id
            try {
                if (Schema::hasTable('email_plantillas_traduccion') && Schema::hasColumn('email_plantillas_traduccion', 'idioma_id')) {
                    DB::table('email_plantillas_traduccion')->where('idioma_id', $oldId)->update(['idioma_id' => $newId]);
                }
            } catch (Throwable $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        // No se revierte la normalización de IDs; solo eliminamos la tabla nueva.
        Schema::dropIfExists('pg_general_traduccion');
    }
};
