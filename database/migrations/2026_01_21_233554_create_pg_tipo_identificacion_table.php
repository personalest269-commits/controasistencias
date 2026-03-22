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
        Schema::create('pg_tipo_identificacion', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('codigo', 5)->unique();
            $table->string('descripcion', 255);
            $table->tinyInteger('estado_actual')->default(1);
            $table->tinyInteger('asocia_persona')->default(0);
            $table->tinyInteger('validar')->default(0);
            $table->integer('longitud')->nullable();
            $table->tinyInteger('longitud_fija')->default(0);
            $table->string('codigo_sri', 10)->nullable();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_tipo_identificacion');
    }
};
