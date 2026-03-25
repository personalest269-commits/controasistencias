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
        if (Schema::hasTable('pg_configuraciones_bases')) {
            return;
        }

        Schema::create('pg_configuraciones_bases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 120)->unique();
            $table->string('driver', 20)->default('mysql'); // mysql|pgsql|sqlsrv|sqlite
            $table->string('host', 150)->nullable();
            $table->string('port', 10)->nullable();
            $table->string('database', 150);
            $table->string('schema', 80)->nullable(); // útil para pgsql
            $table->string('username', 120)->nullable();
            $table->text('password')->nullable();
            $table->string('charset', 40)->nullable();
            $table->string('collation', 80)->nullable();
            $table->char('activo', 1)->default('S'); // S/N
            $table->char('estado', 1)->nullable(); // X = eliminado lógico
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['driver', 'activo']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_configuraciones_bases');
    }
};
