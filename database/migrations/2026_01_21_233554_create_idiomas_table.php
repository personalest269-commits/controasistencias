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
        Schema::create('pg_idiomas', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('codigo', 10)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->boolean('por_defecto')->default(false);
            $table->timestamps();
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_idiomas');
    }
};
