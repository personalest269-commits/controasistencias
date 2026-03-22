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
        Schema::table('pg_general_traduccion', function (Blueprint $table) {
            $table->foreign(['idioma_codigo'], 'fk_pg_general_traduccion_idiomas_codigo')->references(['codigo'])->on('pg_idiomas')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['id_dioma'], 'fk_pg_general_traduccion_pg_idiomas_id')->references(['id'])->on('pg_idiomas')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_general_traduccion', function (Blueprint $table) {
            $table->dropForeign('fk_pg_general_traduccion_idiomas_codigo');
            $table->dropForeign('fk_pg_general_traduccion_pg_idiomas_id');
        });
    }
};
