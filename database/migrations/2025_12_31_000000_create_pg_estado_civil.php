<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_estado_civil')) {
            Schema::create('pg_estado_civil', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('codigo', 5)->unique();
                $table->string('descripcion', 255);
                // NULL = activo, 'X' = eliminado lógico
                $table->char('estado', 1)->nullable()->default(null);
                $table->timestamps();
                $table->index(['estado']);
            });
        } else {
            // Asegurar columnas principales (por si la tabla existe parcial)
            Schema::table('pg_estado_civil', function (Blueprint $table) {
                if (!Schema::hasColumn('pg_estado_civil', 'codigo')) {
                    $table->string('codigo', 5)->unique();
                }
                if (!Schema::hasColumn('pg_estado_civil', 'descripcion')) {
                    $table->string('descripcion', 255)->nullable();
                }
                if (!Schema::hasColumn('pg_estado_civil', 'estado')) {
                    $table->char('estado', 1)->nullable()->default(null);
                    $table->index(['estado']);
                }
            });
        }

        // Seed base (idempotente)
        $base = [
            ['codigo' => '1', 'descripcion' => 'SOLTERO'],
            ['codigo' => '2', 'descripcion' => 'CASADO'],
            ['codigo' => '3', 'descripcion' => 'DIVORCIADO'],
            ['codigo' => '4', 'descripcion' => 'VIUDO'],
            ['codigo' => '5', 'descripcion' => 'UNION LIBRE'],
        ];

        foreach ($base as $row) {
            $exists = DB::table('pg_estado_civil')->where('codigo', $row['codigo'])->exists();
            if (!$exists) {
                DB::table('pg_estado_civil')->insert([
                    'codigo' => $row['codigo'],
                    'descripcion' => $row['descripcion'],
                    'estado' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_estado_civil');
    }
};
