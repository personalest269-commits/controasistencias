<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_tipo_identificacion')) {
            Schema::create('pg_tipo_identificacion', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('codigo', 5)->unique();
                $table->string('descripcion', 255);

                // Columnas según tu tabla fuente
                $table->tinyInteger('estado_actual')->default(1);
                $table->tinyInteger('asocia_persona')->default(0);
                $table->tinyInteger('validar')->default(0);
                $table->integer('longitud')->nullable();
                $table->tinyInteger('longitud_fija')->default(0);
                $table->string('codigo_sri', 10)->nullable();

                // NULL = activo, 'X' = eliminado lógico
                $table->char('estado', 1)->nullable()->default(null);
                $table->timestamps();

                $table->index(['estado']);
            });
        }

        // Seed base (idempotente)
        $base = [
            ['codigo' => '5', 'descripcion' => 'CERTIFICADO DE VOTACION', 'estado_actual' => 1, 'asocia_persona' => 0, 'validar' => 0, 'longitud' => 10, 'longitud_fija' => 1, 'codigo_sri' => null],
            ['codigo' => '4', 'descripcion' => 'LIBRETA MILITAR', 'estado_actual' => 1, 'asocia_persona' => 0, 'validar' => 0, 'longitud' => 10, 'longitud_fija' => 1, 'codigo_sri' => null],
            ['codigo' => '1', 'descripcion' => 'R.U.C.', 'estado_actual' => 1, 'asocia_persona' => 1, 'validar' => 0, 'longitud' => 13, 'longitud_fija' => 1, 'codigo_sri' => '04'],
            ['codigo' => '2', 'descripcion' => 'CEDULA DE IDENTIDAD', 'estado_actual' => 1, 'asocia_persona' => 1, 'validar' => 1, 'longitud' => 10, 'longitud_fija' => 1, 'codigo_sri' => '05'],
            ['codigo' => '3', 'descripcion' => 'PASAPORTE', 'estado_actual' => 1, 'asocia_persona' => 1, 'validar' => 0, 'longitud' => 7, 'longitud_fija' => 1, 'codigo_sri' => '06'],
            ['codigo' => '6', 'descripcion' => 'ACUERDO MINISTERIAL', 'estado_actual' => 1, 'asocia_persona' => 1, 'validar' => 0, 'longitud' => 10, 'longitud_fija' => 0, 'codigo_sri' => null],
            ['codigo' => '7', 'descripcion' => 'DOCUMENTO DE IDENTIFICACION DE REFUGIADO', 'estado_actual' => 1, 'asocia_persona' => 1, 'validar' => 0, 'longitud' => 10, 'longitud_fija' => 0, 'codigo_sri' => null],
            ['codigo' => '8', 'descripcion' => 'VENTA A CONSUMIDOR FINAL', 'estado_actual' => 1, 'asocia_persona' => 1, 'validar' => 0, 'longitud' => 13, 'longitud_fija' => 1, 'codigo_sri' => '07'],
        ];

        foreach ($base as $row) {
            $exists = DB::table('pg_tipo_identificacion')->where('codigo', $row['codigo'])->exists();
            if (!$exists) {
                DB::table('pg_tipo_identificacion')->insert([
                    'codigo' => $row['codigo'],
                    'descripcion' => $row['descripcion'],
                    'estado_actual' => $row['estado_actual'],
                    'asocia_persona' => $row['asocia_persona'],
                    'validar' => $row['validar'],
                    'longitud' => $row['longitud'],
                    'longitud_fija' => $row['longitud_fija'],
                    'codigo_sri' => $row['codigo_sri'],
                    'estado' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pg_tipo_identificacion');
    }
};
