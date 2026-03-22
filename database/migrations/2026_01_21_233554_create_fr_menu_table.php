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
        Schema::create('fr_menu', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->integer('orden')->default(1)->index();
            $table->string('texto_es', 120);
            $table->string('texto_en', 120)->nullable();
            $table->string('tipo', 20)->default('anchor');
            $table->string('destino', 600)->nullable();
            $table->char('nuevo_tab', 1)->nullable()->default('N');
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fr_menu');
    }
};
