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
        Schema::create('pg_configuraciones', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('clave', 120)->unique();
            $table->longText('valor')->nullable();
            $table->string('tipo', 30)->default('texto');
            $table->string('descripcion', 255)->nullable();
            $table->string('grupo', 50)->default('general')->index();
            $table->char('activo', 1)->default('S')->index();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_configuraciones');
    }
};
