<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pg_idiomas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique(); // en, es
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->boolean('por_defecto')->default(false);
            $table->timestamps();
        });

        // Only two languages: English + Spanish.
        DB::table('pg_idiomas')->insert([
            [
                'codigo' => 'en',
                'nombre' => 'English',
                'activo' => 1,
                'por_defecto' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'es',
                'nombre' => 'Español',
                'activo' => 1,
                'por_defecto' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_idiomas');
    }
};
