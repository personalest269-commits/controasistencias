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
        Schema::create('pg_importacion_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('batch_id', 36)->index();
            $table->string('identificacion', 15)->nullable()->index();
            $table->string('persona_id', 10)->nullable()->index();
            $table->string('accion', 10);
            $table->string('mensaje_error')->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_importacion_logs');
    }
};
