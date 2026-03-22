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
        Schema::create('pg_importacion_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('batch_id', 36)->unique();
            $table->string('fuente', 10);
            $table->string('archivo_nombre')->nullable();
            $table->text('api_url')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('estado', 20)->default('CARGADO');
            $table->integer('total_registros')->default(0);
            $table->integer('total_vigentes')->default(0);
            $table->integer('total_insert')->default(0);
            $table->integer('total_update')->default(0);
            $table->integer('total_bajas')->default(0);
            $table->integer('total_errores')->default(0);
            $table->timestamp('aplicado_at')->nullable();
            $table->timestamp('rollback_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_importacion_batches');
    }
};
