<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function nextId(string $table): string
    {
        $row = DB::selectOne("SELECT LPAD(IFNULL(MAX(CAST(id AS UNSIGNED)), 0) + 1, 10, '0') AS id FROM {$table}");
        return (string) ($row->id ?? '0000000001');
    }

    public function up(): void
    {
        if (!\Schema::hasTable('pg_opcion_menu')) {
            return;
        }

        $padre = DB::table('pg_opcion_menu')->where('titulo', 'Gestión de Importaciones')->first();
        if (!$padre) {
            $idPadre = $this->nextId('pg_opcion_menu');
            DB::table('pg_opcion_menu')->insert([
                'id' => $idPadre,
                'titulo' => 'Gestión de Importaciones',
                'id_padre' => null,
                'url' => null,
                'tipo' => 'G',
                'activo' => 'S',
                'orden' => 90,
                'id_archivo' => null,
                'estado' => null,
            ]);
        } else {
            $idPadre = (string) $padre->id;
        }

        $exists = DB::table('pg_opcion_menu')->where('url', '/importaciones-empresa')->exists();
        if (!$exists) {
            DB::table('pg_opcion_menu')->insert([
                'id' => $this->nextId('pg_opcion_menu'),
                'titulo' => 'Importaciones Empresa',
                'id_padre' => $idPadre,
                'url' => '/importaciones-empresa',
                'tipo' => 'M',
                'activo' => 'S',
                'orden' => 3,
                'id_archivo' => null,
                'estado' => null,
            ]);
        }
    }

    public function down(): void
    {
        // No-op por seguridad de datos.
    }
};
