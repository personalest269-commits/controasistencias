<?php

namespace App\Services;

use App\Models\PgAsistenciaEvento;
use App\Models\PgEvento;
use App\Models\PgJustificacionAsistencia;
use App\Models\PgPersona;
use App\Services\PackedAttendanceService;
use Illuminate\Support\Facades\DB;

class CierreAsistenciaService
{
    /**
     * Cierra asistencia del día: registra faltas (F) para eventos aplicables
     * cuando no existe asistencia A y no hay justificación aprobada.
     *
     * @return array{total_personas:int,total_eventos:int,faltas_nuevas:int,faltas_actualizadas:int}
     */
    public static function cerrarDia(string $fecha, ?array $personaIds = null, string $uid = ''): array
    {
        // Personas
        if ($personaIds === null) {
            $personas = PgPersona::query()
                ->select(['id', 'departamento_id'])
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->get();
        } else {
            $personaIds = array_values(array_unique(array_filter($personaIds)));
            if (empty($personaIds)) {
                return ['total_personas' => 0, 'total_eventos' => 0, 'faltas_nuevas' => 0, 'faltas_actualizadas' => 0];
            }
            $personas = PgPersona::query()
                ->select(['id', 'departamento_id'])
                ->whereIn('id', $personaIds)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->get();
        }

        $personIds = $personas->pluck('id')->map(fn($v) => (string) $v)->all();
        if (empty($personIds)) {
            return ['total_personas' => 0, 'total_eventos' => 0, 'faltas_nuevas' => 0, 'faltas_actualizadas' => 0];
        }

        // Eventos del día
        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';
        $events = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->get();

        if ($events->isEmpty()) {
            return ['total_personas' => count($personIds), 'total_eventos' => 0, 'faltas_nuevas' => 0, 'faltas_actualizadas' => 0];
        }

        $eventIds = $events->pluck('id')->map(fn($v) => (string) $v)->all();
        $targets = self::loadEventTargets($eventIds);

        // Justificaciones aprobadas del día
        $justs = PgJustificacionAsistencia::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('persona_id', $personIds)
            ->where('estado_revision', 'A')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id']);

        $justMap = [];
        foreach ($justs as $j) {
            $justMap[(string) $j->persona_id][(string) $j->evento_id] = true;
        }

        // Asistencias compactas existentes del día por persona
        $existingPacked = PgAsistenciaEvento::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('persona_id', $personIds)
            ->get();

        $existingPackedMap = [];
        foreach ($existingPacked as $a) {
            $existingPackedMap[(string) $a->persona_id] = $a;
        }

        $faltasNuevas = 0;
        $faltasActualizadas = 0;

        DB::beginTransaction();
        try {
            foreach ($personas as $p) {
                $pid = (string) $p->id;
                $depId = $p->departamento_id ? (string) $p->departamento_id : null;

                foreach ($events as $e) {
                    $eid = (string) $e->id;
                    if (!self::eventAppliesToPerson($eid, $pid, $depId, $targets)) {
                        continue;
                    }

                    $packedRow = $existingPackedMap[$pid] ?? null;
                    $packed = $packedRow ? PackedAttendanceService::readPacked($packedRow) : [
                        'evento_id' => [],
                        'id_archivo' => [],
                        'estado_asistencia' => [],
                        'observacion' => [],
                    ];
                    $idx = array_search($eid, $packed['evento_id'], true);
                    $currentState = $idx === false ? '' : (string) ($packed['estado_asistencia'][$idx] ?? '');

                    // Si asistió, no tocar
                    if ($currentState === 'A' && ($packedRow->estado === null || $packedRow->estado !== 'X')) {
                        continue;
                    }

                    // Si justificó, no registrar falta
                    if (!empty($justMap[$pid][$eid])) {
                        continue;
                    }

                    if ($idx === false) {
                        $faltasNuevas++;
                    } else {
                        if ($currentState !== 'F') {
                            $faltasActualizadas++;
                        }
                    }

                    $updated = PackedAttendanceService::updatePackedAttendance(
                        $pid,
                        $fecha,
                        $eid,
                        'F',
                        $idx === false ? null : (($packed['id_archivo'][$idx] ?? '') ?: null),
                        $idx === false ? null : (($packed['observacion'][$idx] ?? '') ?: null),
                        $uid
                    );
                    $existingPackedMap[$pid] = $updated;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'total_personas' => count($personIds),
            'total_eventos' => count($eventIds),
            'faltas_nuevas' => $faltasNuevas,
            'faltas_actualizadas' => $faltasActualizadas,
        ];
    }

    private static function loadEventTargets(array $eventIds): array
    {
        $eventIds = array_values(array_unique(array_filter($eventIds)));
        if (empty($eventIds)) {
            return ['deps' => [], 'pers' => []];
        }

        $deps = DB::table('pg_evento_departamento')
            ->whereIn('evento_id', $eventIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();

        $pers = DB::table('pg_evento_persona')
            ->whereIn('evento_id', $eventIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();

        $depMap = [];
        foreach ($deps as $r) {
            $depMap[(string) $r->evento_id][] = (string) $r->departamento_id;
        }
        $perMap = [];
        foreach ($pers as $r) {
            $perMap[(string) $r->evento_id][] = (string) $r->persona_id;
        }

        foreach ($depMap as $eid => $arr) {
            $depMap[$eid] = array_values(array_unique($arr));
        }
        foreach ($perMap as $eid => $arr) {
            $perMap[$eid] = array_values(array_unique($arr));
        }

        return ['deps' => $depMap, 'pers' => $perMap];
    }

    private static function eventAppliesToPerson(string $eventoId, string $personaId, ?string $departamentoId, array $targets): bool
    {
        $deps = $targets['deps'][$eventoId] ?? [];
        $pers = $targets['pers'][$eventoId] ?? [];

        // Global
        if (empty($deps) && empty($pers)) {
            return true;
        }

        // Targeted by person
        if (in_array($personaId, $pers, true)) {
            return true;
        }

        // Targeted by department
        if ($departamentoId && in_array($departamentoId, $deps, true)) {
            return true;
        }

        return false;
    }
}
