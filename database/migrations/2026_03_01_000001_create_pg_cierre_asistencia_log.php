<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pg_cierre_asistencia_log')) {
            return;
        }

        Schema::create('pg_cierre_asistencia_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('fecha');

            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->string('status', 20)->default('RUNNING'); // OK | ERROR | RUNNING
            $table->text('message')->nullable();

            $table->integer('total_personas')->default(0);
            $table->integer('total_eventos')->default(0);
            $table->integer('faltas_nuevas')->default(0);
            $table->integer('faltas_actualizadas')->default(0);
            $table->string('run_by', 50)->nullable();

            $table->timestamps();

            $table->index(['fecha']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_cierre_asistencia_log');
    }
};
