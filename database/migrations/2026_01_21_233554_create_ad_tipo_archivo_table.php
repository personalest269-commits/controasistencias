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
        Schema::create('ad_tipo_archivo', function (Blueprint $table) {
            $table->string('codigo', 5)->primary();
            $table->string('descripcion', 255);
            $table->string('tipo_mime', 255);
            $table->string('extension', 10);
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_tipo_archivo');
    }
};
