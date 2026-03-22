<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_opcion_menu')) {
            Schema::create('pg_opcion_menu', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('titulo', 255);
                $table->unsignedBigInteger('id_padre')->nullable();
                $table->string('url', 255)->nullable();

                // G = Grupo, M = Módulo (navega)
                $table->char('tipo', 1)->default('G');

                // S = activo, N = inactivo (además del estado lógico)
                $table->char('activo', 1)->default('S');

                $table->smallInteger('orden')->default(0);

                // Imagen del menú (opcional). Se guarda en ad_archivo_digital.digital
                $table->unsignedBigInteger('id_archivo')->nullable();

                // Eliminación lógica: NULL = activo, 'X' = eliminado
                $table->char('estado', 1)->nullable()->default(null);

                $table->index(['estado']);
                $table->index(['id_padre']);
                $table->index(['activo']);
            });
        }

        if (!Schema::hasTable('pg_opcion_menu_rol')) {
            Schema::create('pg_opcion_menu_rol', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->unsignedBigInteger('id_opcion_menu');
                // Nombre del rol (roles.name)
                $table->string('rol', 30);

                // Eliminación lógica
                $table->char('estado', 1)->nullable()->default(null);

                $table->index(['estado']);
                $table->index(['rol']);
                $table->unique(['id_opcion_menu', 'rol']);
            });
        }

        // Relaciones (FK) si existen las tablas
        if (Schema::hasTable('pg_opcion_menu')) {
            Schema::table('pg_opcion_menu', function (Blueprint $table) {
                // FK self
                try {
                    $table->foreign('id_padre')->references('id')->on('pg_opcion_menu')
                        ->onUpdate('cascade')->onDelete('set null');
                } catch (\Throwable $e) {
                    // ignore
                }

                // FK a archivos digitales
                if (Schema::hasTable('ad_archivo_digital')) {
                    try {
                        $table->foreign('id_archivo')->references('id')->on('ad_archivo_digital')
                            ->onUpdate('cascade')->onDelete('set null');
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            });
        }

        if (Schema::hasTable('pg_opcion_menu_rol') && Schema::hasTable('pg_opcion_menu')) {
            Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
                try {
                    $table->foreign('id_opcion_menu')->references('id')->on('pg_opcion_menu')
                        ->onUpdate('cascade')->onDelete('cascade');
                } catch (\Throwable $e) {
                    // ignore
                }
            });
        }
    }

    public function down(): void
    {
        // No se elimina por seguridad (pueden estar personalizadas en producción)
    }
};
