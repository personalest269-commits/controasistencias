<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        Schema::table('pg_usuario', function (Blueprint $table) {
            if (!Schema::hasColumn('pg_usuario', 'id_persona')) {
                $table->string('id_persona', 10)->nullable()->after('id');
                $table->index(['id_persona']);
            }
        });

        // Agregar FK fuera del closure para poder comprobar que la tabla referenciada exista
        if (Schema::hasTable('pg_persona')) {
            try {
                Schema::table('pg_usuario', function (Blueprint $table) {
                    // Laravel genera nombres automáticos, pero evitamos duplicar si ya existe
                    $table->foreign('id_persona')->references('id')->on('pg_persona')
                        ->onUpdate('cascade')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // Si la FK ya existe o el motor no la permite, no bloqueamos la migración.
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        try {
            Schema::table('pg_usuario', function (Blueprint $table) {
                // Nombre convencional del constraint si Laravel lo creó
                $table->dropForeign(['id_persona']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('pg_usuario', function (Blueprint $table) {
            if (Schema::hasColumn('pg_usuario', 'id_persona')) {
                $table->dropColumn('id_persona');
            }
        });
    }
};
