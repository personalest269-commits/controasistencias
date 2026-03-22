<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmpresaIdToPgDepartamentoTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pg_departamento')) {
            return;
        }

        Schema::table('pg_departamento', function (Blueprint $table) {
            if (!Schema::hasColumn('pg_departamento', 'empresa_id')) {
                $table->string('empresa_id', 10)->nullable()->after('id');
                $table->index('empresa_id', 'pg_departamento_empresa_id_idx');
            }
        });

        // FK separado para evitar fallos si ya existe
        try {
            Schema::table('pg_departamento', function (Blueprint $table) {
                // Laravel no tiene hasForeign, así que intentamos crear y si existe lo ignoramos
                $table->foreign('empresa_id', 'pg_departamento_empresa_fk')
                    ->references('id')->on('pg_empresa');
            });
        } catch (\Throwable $e) {
            // Ignorar si ya existe
        }
    }

    public function down()
    {
        if (!Schema::hasTable('pg_departamento')) {
            return;
        }

        try {
            Schema::table('pg_departamento', function (Blueprint $table) {
                $table->dropForeign('pg_departamento_empresa_fk');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('pg_departamento', function (Blueprint $table) {
            if (Schema::hasColumn('pg_departamento', 'empresa_id')) {
                $table->dropIndex('pg_departamento_empresa_id_idx');
                $table->dropColumn('empresa_id');
            }
        });
    }
}
