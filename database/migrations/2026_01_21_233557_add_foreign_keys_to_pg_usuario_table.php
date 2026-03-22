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
        Schema::table('pg_usuario', function (Blueprint $table) {
            $table->foreign(['id_archivo'], 'fk_pg_usuario_archivo_digital')->references(['id'])->on('ad_archivo_digital')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['id_plantillas'], 'fk_pg_usuario_pg_plantillas')->references(['id'])->on('pg_plantillas')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_usuario', function (Blueprint $table) {
            $table->dropForeign('fk_pg_usuario_archivo_digital');
            $table->dropForeign('fk_pg_usuario_pg_plantillas');
        });
    }
};
