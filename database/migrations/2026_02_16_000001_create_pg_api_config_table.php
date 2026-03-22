<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_api_config')) {
            Schema::create('pg_api_config', function (Blueprint $table) {
                $table->bigIncrements('id');
                // Ejemplo de clave: personas_import
                $table->string('clave', 100)->unique();

                $table->text('api_url')->nullable();
                $table->string('auth_type', 20)->default('none'); // none|basic|bearer
                $table->string('auth_user', 150)->nullable();
                $table->string('auth_pass', 150)->nullable();
                $table->string('auth_token', 500)->nullable();

                // Query params por defecto (JSON)
                $table->json('query_params')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_api_config');
    }
};
