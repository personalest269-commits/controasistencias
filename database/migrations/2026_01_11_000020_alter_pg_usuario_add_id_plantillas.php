<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        Schema::table('pg_usuario', function (Blueprint $table) {
            // Si existe ui_template lo eliminamos (se reemplaza por id_plantillas)
            if (Schema::hasColumn('pg_usuario', 'ui_template')) {
                $table->dropColumn('ui_template');
            }

            if (!Schema::hasColumn('pg_usuario', 'id_plantillas')) {
                $table->unsignedBigInteger('id_plantillas')->nullable()->after('id');
            }
        });

        // Agregar FK si es posible (envolvemos en try/catch para no romper en motores/instalaciones distintas)
        try {
            Schema::table('pg_usuario', function (Blueprint $table) {
                $table->foreign('id_plantillas', 'fk_pg_usuario_pg_plantillas')
                    ->references('id')
                    ->on('pg_plantillas');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // Default: asignar a todos los usuarios la plantilla gentelella si no tienen valor
        try {
            $gId = DB::table('pg_plantillas')->where('codigo', 'gentelella')->value('id');
            if ($gId) {
                DB::table('pg_usuario')->whereNull('id_plantillas')->update(['id_plantillas' => $gId]);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        try {
            Schema::table('pg_usuario', function (Blueprint $table) {
                try { $table->dropForeign('fk_pg_usuario_pg_plantillas'); } catch (\Throwable $e) {}
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('pg_usuario', function (Blueprint $table) {
            if (Schema::hasColumn('pg_usuario', 'id_plantillas')) {
                $table->dropColumn('id_plantillas');
            }
        });
    }
};
