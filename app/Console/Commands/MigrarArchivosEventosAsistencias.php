<?php

namespace App\Console\Commands;

use App\Services\PackedAttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrarArchivosEventosAsistencias extends Command
{
    protected $signature = 'archivos:migrar-eventos-asistencias
                            {--chunk=500 : Cantidad de IDs por lote}
                            {--dry-run : Solo mostrar conteos, sin insertar en esquema destino}';

    protected $description = 'Migra a mysql_archivos archivos referenciados desde pg_asistencia_evento, pg_asistencia_lote y pg_asistencia_lote_archivo';

    public function handle(): int
    {
        $targetConnection = 'mysql_archivos';
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if (!Schema::connection($targetConnection)->hasTable('ad_archivo_digital')) {
            $this->error('No existe la tabla destino mysql_archivos.ad_archivo_digital. Ejecuta primero las migraciones.');
            return self::FAILURE;
        }

        $allIds = $this->idsDeEventosYAsistencias();
        $totalIds = count($allIds);

        $migrados = 0;
        $faltantes = 0;
        $docNull = 0;
        $docFilled = 0;
        $tipoNull = 0;
        $tipoFilled = 0;

        foreach (array_chunk($allIds, $chunk) as $batchIds) {
            $stats = $this->migrarLote($batchIds, $targetConnection, $dryRun);
            $migrados += $stats['migrados'];
            $faltantes += $stats['faltantes'];
            $docNull += $stats['doc_null'];
            $docFilled += $stats['doc_filled'];
            $tipoNull += $stats['tipo_null'];
            $tipoFilled += $stats['tipo_filled'];
        }

        $this->info('IDs únicos detectados (eventos/asistencias): ' . $totalIds);
        $this->info(($dryRun ? 'Registros analizables' : 'Registros migrados/actualizados') . ': ' . $migrados);
        $this->info('tipo_documento_codigo -> con valor: ' . $docFilled . ' | nulo: ' . $docNull);
        $this->info('tipo_archivo_codigo   -> con valor: ' . $tipoFilled . ' | nulo: ' . $tipoNull);

        if ($faltantes > 0) {
            $this->warn('IDs sin fuente en ad_archivo_digital: ' . $faltantes);
        }

        return self::SUCCESS;
    }

    private function idsDeEventosYAsistencias(): array
    {
        $ids = [];

        if (Schema::hasTable('pg_asistencia_lote_archivo') && Schema::hasColumn('pg_asistencia_lote_archivo', 'id_archivo')) {
            DB::table('pg_asistencia_lote_archivo')
                ->select('id_archivo')
                ->whereNotNull('id_archivo')
                ->orderBy('id')
                ->chunkById(1000, function ($rows) use (&$ids) {
                    foreach ($rows as $row) {
                        $id = trim((string) ($row->id_archivo ?? ''));
                        if ($id !== '') {
                            $ids[$id] = true;
                        }
                    }
                }, 'id');
        }

        if (Schema::hasTable('pg_asistencia_evento') && Schema::hasColumn('pg_asistencia_evento', 'id_archivo')) {
            DB::table('pg_asistencia_evento')
                ->select('id_archivo')
                ->whereNotNull('id_archivo')
                ->orderBy('id')
                ->chunkById(1000, function ($rows) use (&$ids) {
                    foreach ($rows as $row) {
                        $raw = (string) ($row->id_archivo ?? '');
                        if (trim($raw) === '') {
                            continue;
                        }
                        try {
                            $decoded = PackedAttendanceService::decodeList($raw);
                        } catch (\Throwable $e) {
                            $decoded = [trim($raw)];
                        }

                        foreach ($decoded as $id) {
                            $id = trim((string) $id);
                            if ($id !== '') {
                                $ids[$id] = true;
                            }
                        }
                    }
                }, 'id');
        }

        if (Schema::hasTable('pg_asistencia_lote') && Schema::hasColumn('pg_asistencia_lote', 'id_archivo')) {
            DB::table('pg_asistencia_lote')
                ->select('id_archivo')
                ->whereNotNull('id_archivo')
                ->orderBy('id')
                ->chunkById(1000, function ($rows) use (&$ids) {
                    foreach ($rows as $row) {
                        $raw = (string) ($row->id_archivo ?? '');
                        if (trim($raw) === '') {
                            continue;
                        }
                        try {
                            $decoded = PackedAttendanceService::decodeList($raw);
                        } catch (\Throwable $e) {
                            $decoded = [trim($raw)];
                        }

                        foreach ($decoded as $id) {
                            $id = trim((string) $id);
                            if ($id !== '') {
                                $ids[$id] = true;
                            }
                        }
                    }
                }, 'id');
        }

        return array_keys($ids);
    }

    private function migrarLote(array $ids, string $targetConnection, bool $dryRun): array
    {
        $uniqueIds = array_values(array_unique($ids));

        $rows = DB::table('ad_archivo_digital')
            ->whereIn('id', $uniqueIds)
            ->get();

        $faltantes = count($uniqueIds) - $rows->count();

        if ($rows->isEmpty()) {
            return ['migrados' => 0, 'faltantes' => $faltantes, 'doc_null' => 0, 'doc_filled' => 0, 'tipo_null' => 0, 'tipo_filled' => 0];
        }

        $docNull = 0;
        $docFilled = 0;
        $tipoNull = 0;
        $tipoFilled = 0;

        foreach ($rows as $row) {
            if (empty($row->tipo_documento_codigo)) {
                $docNull++;
            } else {
                $docFilled++;
            }

            if (empty($row->tipo_archivo_codigo)) {
                $tipoNull++;
            } else {
                $tipoFilled++;
            }
        }

        if ($dryRun) {
            return ['migrados' => $rows->count(), 'faltantes' => $faltantes, 'doc_null' => $docNull, 'doc_filled' => $docFilled, 'tipo_null' => $tipoNull, 'tipo_filled' => $tipoFilled];
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

        return ['migrados' => count($payload), 'faltantes' => $faltantes, 'doc_null' => $docNull, 'doc_filled' => $docFilled, 'tipo_null' => $tipoNull, 'tipo_filled' => $tipoFilled];
    }
}
