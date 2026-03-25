<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrarArchivosEventosAsistencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archivos:migrar-eventos-asistencias
                            {--chunk=500 : Cantidad de IDs por lote}
                            {--dry-run : Solo mostrar conteos, sin insertar en esquema destino}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra a mysql_archivos solo los archivos usados por eventos y asistencias';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetConnection = 'mysql_archivos';
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if (!Schema::connection($targetConnection)->hasTable('ad_archivo_digital')) {
            $this->error('No existe la tabla destino mysql_archivos.ad_archivo_digital. Ejecuta primero las migraciones.');
            return self::FAILURE;
        }

        $idCursor = $this->idsDeEventosYAsistencias()->cursor();

        $batchIds = [];
        $totalIds = 0;
        $migrados = 0;
        $faltantes = 0;

        foreach ($idCursor as $row) {
            $id = trim((string) ($row->id ?? ''));
            if ($id === '') {
                continue;
            }

            $batchIds[] = $id;
            $totalIds++;

            if (count($batchIds) >= $chunk) {
                [$migradosLote, $faltantesLote] = $this->migrarLote($batchIds, $targetConnection, $dryRun);
                $migrados += $migradosLote;
                $faltantes += $faltantesLote;
                $batchIds = [];
            }
        }

        if (!empty($batchIds)) {
            [$migradosLote, $faltantesLote] = $this->migrarLote($batchIds, $targetConnection, $dryRun);
            $migrados += $migradosLote;
            $faltantes += $faltantesLote;
        }

        $this->info('IDs detectados (eventos/asistencias): ' . $totalIds);
        $this->info(($dryRun ? 'Registros analizables' : 'Registros migrados/actualizados') . ': ' . $migrados);
        if ($faltantes > 0) {
            $this->warn('IDs sin fuente en ad_archivo_digital: ' . $faltantes);
        }

        return self::SUCCESS;
    }

    private function idsDeEventosYAsistencias()
    {
        $asistenciaEvento = DB::table('pg_asistencia_evento')
            ->selectRaw('TRIM(id_archivo) as id')
            ->whereNotNull('id_archivo')
            ->whereRaw("TRIM(id_archivo) <> ''");

        $asistenciaLote = DB::table('pg_asistencia_lote_archivo')
            ->selectRaw('TRIM(id_archivo) as id')
            ->whereNotNull('id_archivo')
            ->whereRaw("TRIM(id_archivo) <> ''");

        return $asistenciaEvento
            ->union($asistenciaLote)
            ->orderBy('id');
    }

    private function migrarLote(array $ids, string $targetConnection, bool $dryRun): array
    {
        $uniqueIds = array_values(array_unique($ids));

        $rows = DB::table('ad_archivo_digital')
            ->whereIn('id', $uniqueIds)
            ->get();

        $faltantes = count($uniqueIds) - $rows->count();

        if ($rows->isEmpty()) {
            return [0, $faltantes];
        }

        if ($dryRun) {
            return [$rows->count(), $faltantes];
        }

        $payload = $rows->map(function ($row) {
            return [
                'id' => (string) $row->id,
                'tipo_documento_codigo' => $row->tipo_documento_codigo,
                'tipo_archivo_codigo' => $row->tipo_archivo_codigo,
                'nombre_original' => $row->nombre_original,
                'ruta' => $row->ruta,
                'digital' => $row->digital,
                'tipo_mime' => $row->tipo_mime,
                'extension' => $row->extension,
                'tamano' => $row->tamano,
                'descripcion' => $row->descripcion,
                'estado' => $row->estado,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        })->all();

        DB::connection($targetConnection)
            ->table('ad_archivo_digital')
            ->upsert(
                $payload,
                ['id'],
                [
                    'tipo_documento_codigo',
                    'tipo_archivo_codigo',
                    'nombre_original',
                    'ruta',
                    'digital',
                    'tipo_mime',
                    'extension',
                    'tamano',
                    'descripcion',
                    'estado',
                    'created_at',
                    'updated_at',
                ]
            );

        return [count($payload), $faltantes];
    }
}
