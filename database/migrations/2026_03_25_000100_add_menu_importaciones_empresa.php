<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $ok = $this->safeMenuInsert([
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
            if (!$ok) {
                return;
            }
        } else {
            $idPadre = (string) $padre->id;
        }

        $exists = DB::table('pg_opcion_menu')->where('url', '/importaciones-empresa')->exists();
        if (!$exists) {
            $this->safeMenuInsert([
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

    /**
     * Inserta menú de forma resiliente. Si el ambiente tiene triggers/definers
     * inválidos (MySQL 1449), no tumba el proceso de migrate.
     */
    private function safeMenuInsert(array $data): bool
    {
        try {
            DB::table('pg_opcion_menu')->insert($data);
            return true;
        } catch (QueryException $e) {
            $msg = (string) $e->getMessage();
            if (str_contains($msg, '1449') || str_contains($msg, 'definer')) {
                Log::warning('Se omitió seed de menú Importaciones Empresa por definer inválido en trigger', [
                    'error' => $msg,
                    'menu' => $data['titulo'] ?? null,
                ]);
                return false;
            }

            throw $e;
        }
    }

    public function down(): void
    {
        // No-op por seguridad de datos.
    }
};
