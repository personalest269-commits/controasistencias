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
        Schema::table('pg_permisos_role', function (Blueprint $table) {
            $table->foreign(['permission_id'], 'fk_pg_permisos_role_perm')
                ->references(['id'])->on('pg_permisos')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['role_id'], 'fk_pg_permisos_role_rol')
                ->references(['id'])->on('roles')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_permisos_role', function (Blueprint $table) {
            $table->dropForeign('fk_pg_permisos_role_perm');
            $table->dropForeign('fk_pg_permisos_role_rol');
        });
    }
};
