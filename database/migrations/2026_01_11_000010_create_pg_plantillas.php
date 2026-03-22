<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_plantillas')) {
            Schema::create('pg_plantillas', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('nombre', 50);
                $table->string('codigo', 30)->unique(); // gentelella | admin_lte
                $table->char('activo', 1)->default('S');
                $table->timestamps();
            });
        }

        // Seed mínimo (si no existen)
        $now = now();

        if (!DB::table('pg_plantillas')->where('codigo', 'gentelella')->exists()) {
            DB::table('pg_plantillas')->insert([
                'nombre' => 'Gentelella',
                'codigo' => 'gentelella',
                'activo' => 'S',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!DB::table('pg_plantillas')->where('codigo', 'admin_lte')->exists()) {
            DB::table('pg_plantillas')->insert([
                'nombre' => 'AdminLTE',
                'codigo' => 'admin_lte',
                'activo' => 'S',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_plantillas');
    }
};
