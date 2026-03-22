<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pg_persona_foto')) {
            return;
        }

        Schema::create('pg_persona_foto', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('id_persona', 10);
            $table->unsignedBigInteger('id_archivo');

            // null = activo, 'X' = eliminado lógico
            $table->char('estado', 1)->nullable()->default(null);

            $table->timestamps();

            $table->index(['estado']);
            $table->index(['id_persona']);
            $table->index(['id_archivo']);

            $table->foreign('id_persona')
                ->references('id')->on('pg_persona')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_archivo')
                ->references('id')->on('ad_archivo_digital')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_persona_foto');
    }
};
