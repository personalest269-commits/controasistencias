<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pg_usuario', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->unsignedBigInteger('id_plantillas')->nullable()->index('fk_pg_usuario_pg_plantillas');
            $table->string('id_persona', 10)->nullable()->index();
            $table->string('id_archivo', 10)->nullable()->index('idx_pg_usuario_id_archivo');
            $table->string('name');
            // Login del sistema: usuario = cédula (10 dígitos)
            $table->string('usuario', 10)->nullable()->index();
            // Email queda opcional (solo notificaciones)
            $table->string('email')->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->string('password');
            $table->string('image', 150);
            $table->rememberToken();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->char('estado', 1)->nullable()->index();
        });

        // Únicos "solo activos" (estado IS NULL) usando índice funcional (sin columna extra)
        // Activo = IFNULL(estado,'A') = 'A' | Eliminado = 'X'
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement("CREATE UNIQUE INDEX pg_usuario_usuario_activo_unique ON pg_usuario (usuario, (IFNULL(estado,'A')))");
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                DB::statement("CREATE UNIQUE INDEX pg_usuario_id_persona_activo_unique ON pg_usuario (id_persona, (IFNULL(estado,'A')))");
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_usuario');
    }
};
