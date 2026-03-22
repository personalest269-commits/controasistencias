<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asegura la tabla pg_control para PostgreSQL (y otros drivers) para generar IDs
 * tipo 0000000001 con IdGenerator.
 *
 * En MySQL ya se crea en 2026_01_02_000000_create_pg_control_and_persona_trigger_mysql.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // Si ya existe, no hacemos nada.
        if (Schema::hasTable('pg_control')) {
            return;
        }

        // Crear tabla para drivers distintos de MySQL
        Schema::create('pg_control', function (Blueprint $table) {
            $table->string('objeto', 60);
            $table->string('grupo1', 60)->default('__');
            $table->string('grupo2', 60)->default('______');
            $table->unsignedBigInteger('ultimo')->default(0);
            $table->primary(['objeto', 'grupo1', 'grupo2'], 'pk_pg_control');
        });

        // Para PostgreSQL: asegurar que el PK exista con el nombre correcto.
        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE pg_control ADD CONSTRAINT pk_pg_control PRIMARY KEY (objeto, grupo1, grupo2)');
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // No-op (tabla crítica para IDs)
    }
};
