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
        Schema::table('pg_opcion_menu', function (Blueprint $table) {
            $table->foreign(['id_archivo'])->references(['id'])->on('ad_archivo_digital')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['id_padre'])->references(['id'])->on('pg_opcion_menu')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_opcion_menu', function (Blueprint $table) {
            $table->dropForeign('pg_opcion_menu_id_archivo_foreign');
            $table->dropForeign('pg_opcion_menu_id_padre_foreign');
        });
    }
};
