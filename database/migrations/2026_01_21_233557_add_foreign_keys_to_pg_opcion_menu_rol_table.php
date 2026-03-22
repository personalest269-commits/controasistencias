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
        Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
            $table->foreign(['id_rol'], 'fk_pg_opcion_menu_rol_roles')->references(['id'])->on('roles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['id_opcion_menu'])->references(['id'])->on('pg_opcion_menu')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_opcion_menu_rol', function (Blueprint $table) {
            $table->dropForeign('fk_pg_opcion_menu_rol_roles');
            $table->dropForeign('pg_opcion_menu_rol_id_opcion_menu_foreign');
        });
    }
};
