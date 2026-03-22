<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    private function nextId(string $table): string
    {
        // Genera siguiente ID numérico de 10 dígitos (0000000001...)
        // Funciona aunque NO exista trigger en la tabla.
        $row = DB::selectOne("
            SELECT LPAD(IFNULL(MAX(CAST(id AS UNSIGNED)), 0) + 1, 10, '0') AS id
            FROM {$table}
        ");
        return (string) ($row->id ?? '0000000001');
    }

    public function up(): void
    {
        // Estructura real:
        // pg_opcion_menu: id (varchar10 PK), titulo, id_padre, url, tipo, activo, orden, id_archivo, estado

        // 1) Padre: Gestión de Importaciones
        $padre = DB::table('pg_opcion_menu')->where('titulo', 'Gestión de Importaciones')->first();
        if (!$padre) {
            $idPadre = $this->nextId('pg_opcion_menu');

            DB::table('pg_opcion_menu')->insert([
                'id' => $idPadre,
                'titulo' => 'Gestión de Importaciones',
                'id_padre' => null,
                'url' => null,
                'tipo' => 'G',      // Grupo
                'activo' => 'S',
                'orden' => 90,
                'id_archivo' => null,
                'estado' => null,
            ]);
        } else {
            $idPadre = $padre->id;
        }

        // 2) Hijo: Historial de Importaciones
        $h1 = DB::table('pg_opcion_menu')->where('titulo', 'Historial de Importaciones')->first();
        if (!$h1) {
            DB::table('pg_opcion_menu')->insert([
                'id' => $this->nextId('pg_opcion_menu'),
                'titulo' => 'Historial de Importaciones',
                'id_padre' => $idPadre,
                'url' => '/importaciones',
                'tipo' => 'M',      // Menú/Item
                'activo' => 'S',
                'orden' => 1,
                'id_archivo' => null,
                'estado' => null,
            ]);
        }

        // 3) Hijo: Gestión Log
        $h2 = DB::table('pg_opcion_menu')->where('titulo', 'Gestión Log')->first();
        if (!$h2) {
            DB::table('pg_opcion_menu')->insert([
                'id' => $this->nextId('pg_opcion_menu'),
                'titulo' => 'Gestión Log',
                'id_padre' => $idPadre,
                'url' => '/importaciones-logs',
                'tipo' => 'M',
                'activo' => 'S',
                'orden' => 2,
                'id_archivo' => null,
                'estado' => null,
            ]);
        }
    }

    public function down(): void
    {
        // No eliminamos por seguridad
    }
};
