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
        Schema::create('fr_portafolio', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->integer('orden')->default(1)->index();
            $table->string('titulo_es', 200)->nullable();
            $table->string('titulo_en', 200)->nullable();
            $table->string('categoria_es', 200)->nullable();
            $table->string('categoria_en', 200)->nullable();
            $table->string('imagen_archivo_id', 20)->nullable()->index();
            $table->string('url', 600)->nullable();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fr_portafolio');
    }
};
