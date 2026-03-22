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
        Schema::table('pg_log', function (Blueprint $table) {
            $table->foreign(['usuario_id'], 'fk_pg_log_usuario')->references(['id'])->on('pg_usuario')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_log', function (Blueprint $table) {
            $table->dropForeign('fk_pg_log_usuario');
        });
    }
};
