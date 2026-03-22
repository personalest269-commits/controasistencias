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
        Schema::create('pg_opcion_menu', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('titulo', 255);
            $table->string('id_padre', 10)->nullable()->index();
            $table->string('url', 255)->nullable();
            $table->char('tipo', 1)->default('G');
            $table->char('activo', 1)->default('S')->index();
            $table->smallInteger('orden')->default(0);
            $table->string('id_archivo', 10)->nullable()->index('pg_opcion_menu_id_archivo_foreign');
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_opcion_menu');
    }
};
