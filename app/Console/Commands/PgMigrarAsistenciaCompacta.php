<?php

namespace App\Console\Commands;

use App\Models\PgAsistenciaEvento;
use App\Services\PackedAttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PgMigrarAsistenciaCompacta extends Command
{
    protected $signature = 'pg:migrar-asistencia-compacta {--dry-run : Solo mostrar resumen sin guardar cambios}';

    protected $description = 'Consolida asistencias históricas a 1 registro por persona+fecha en formato compacto JSON compatible.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = PgAsistenciaEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('persona_id')
            ->orderBy('fecha')
            ->orderBy('created_at')
            ->get();

        $groups = [];
        foreach ($rows as $row) {
            $fecha = $row->fecha ? $row->fecha->format('Y-m-d') : null;
            if (!$fecha) {
                continue;
            }
            $k = (string) $row->persona_id . '|' . $fecha;
            $groups[$k][] = $row;
        }

        $totalGrupos = count($groups);
        $totalEventos = 0;
        $totalRowsOriginales = $rows->count();

        DB::beginTransaction();
        try {
            foreach ($groups as $k => $groupRows) {
                [$personaId, $fecha] = explode('|', $k, 2);
                $events = [];

                foreach ($groupRows as $row) {
                    $packed = PackedAttendanceService::readPacked($row);
                    foreach (PackedAttendanceService::packedToEvents($packed) as $event) {
                        $eid = (string) ($event['evento_id'] ?? '');
                        if ($eid === '') {
                            continue;
                        }
                        $events[$eid] = $event;
                    }
                }

                foreach ($events as $event) {
                    $totalEventos++;
                    if ($dryRun) {
                        continue;
                    }
                    PackedAttendanceService::updatePackedAttendance(
                        (string) $personaId,
                        (string) $fecha,
                        (string) $event['evento_id'],
                        (string) ($event['estado_asistencia'] ?? ''),
                        (($event['id_archivo'] ?? '') !== '' ? (string) $event['id_archivo'] : null),
                        (($event['observacion'] ?? '') !== '' ? (string) $event['observacion'] : null)
                    );
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Migración de asistencia compacta finalizada.');
        $this->line('dry-run: ' . ($dryRun ? 'sí' : 'no'));
        $this->line('grupos persona+fecha: ' . $totalGrupos);
        $this->line('filas originales activas: ' . $totalRowsOriginales);
        $this->line('eventos consolidados: ' . $totalEventos);

        return self::SUCCESS;
    }
}
