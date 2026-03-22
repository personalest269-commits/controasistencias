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
        Schema::create('fr_servicio', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->integer('orden')->default(1)->index();
            $table->string('icono', 80)->nullable();
            $table->string('titulo_es', 200);
            $table->string('titulo_en', 200)->nullable();
            $table->text('descripcion_es')->nullable();
            $table->text('descripcion_en')->nullable();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fr_servicio');
    }
};
