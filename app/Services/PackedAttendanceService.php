<?php

namespace App\Services;

use App\Models\PgAsistenciaEvento;
use Illuminate\Support\Collection;

class PackedAttendanceService
{
    /**
     * Soporta 2 formatos:
     * - Nuevo: JSON array (recomendado)
     * - Legacy: CSV separado por comas
     */
    public static function decodeList(?string $raw): array
    {
        $raw = (string) ($raw ?? '');
        if (trim($raw) === '') {
            return [];
        }

        $first = substr(ltrim($raw), 0, 1);
        if ($first === '[') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new \RuntimeException('Lista JSON inválida en asistencia compacta.');
            }
            return array_map(static fn($v) => trim((string) $v), $decoded);
        }

        $values = str_getcsv($raw, ',', '"', '\\');
        return array_map(static fn($v) => trim((string) $v), $values ?: []);
    }

    public static function encodeList(array $values): ?string
    {
        $normalized = array_map(static fn($v) => (string) ($v ?? ''), $values);
        if (empty($normalized)) {
            return null;
        }

        return json_encode(array_values($normalized), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function readPacked(PgAsistenciaEvento $row): array
    {
        $packed = [
            'evento_id' => self::decodeList($row->evento_id),
            'id_archivo' => self::decodeList($row->id_archivo),
            'estado_asistencia' => self::decodeList($row->estado_asistencia),
            'observacion' => self::decodeList($row->observacion),
        ];

        $packed = self::normalizePacked($packed);
        self::validatePacked($packed);

        return $packed;
    }

    /**
     * Normaliza registros legacy para evitar caída por desalineación:
     * - Si hay evento_id y las otras listas están vacías, las rellena con ''.
     * - Si alguna lista es más corta, la completa hasta la longitud de evento_id.
     * - Si alguna lista es más larga, la recorta a longitud de evento_id.
     */
    public static function normalizePacked(array $packed): array
    {
        $eventIds = array_values(array_map(static fn($v) => trim((string) $v), $packed['evento_id'] ?? []));
        $lenEventos = count($eventIds);

        $normalized = [
            'evento_id' => $eventIds,
            'id_archivo' => array_values(array_map(static fn($v) => trim((string) $v), $packed['id_archivo'] ?? [])),
            'estado_asistencia' => array_values(array_map(static fn($v) => trim((string) $v), $packed['estado_asistencia'] ?? [])),
            'observacion' => array_values(array_map(static fn($v) => trim((string) $v), $packed['observacion'] ?? [])),
        ];

        if ($lenEventos === 0) {
            $normalized['id_archivo'] = [];
            $normalized['estado_asistencia'] = [];
            $normalized['observacion'] = [];
            return $normalized;
        }

        foreach (['id_archivo', 'estado_asistencia', 'observacion'] as $field) {
            $len = count($normalized[$field]);
            if ($len < $lenEventos) {
                $normalized[$field] = array_pad($normalized[$field], $lenEventos, '');
            } elseif ($len > $lenEventos) {
                $normalized[$field] = array_slice($normalized[$field], 0, $lenEventos);
            }
        }

        return $normalized;
    }

    public static function validatePacked(array $packed): void
    {
        $lenEventos = count($packed['evento_id'] ?? []);
        foreach (['id_archivo', 'estado_asistencia', 'observacion'] as $field) {
            if (count($packed[$field] ?? []) !== $lenEventos) {
                throw new \RuntimeException('Registro de asistencia compacto inválido: longitudes desalineadas.');
            }
        }

        $seen = [];
        foreach (($packed['evento_id'] ?? []) as $eid) {
            $eid = (string) $eid;
            if ($eid === '') {
                throw new \RuntimeException('Registro de asistencia compacto inválido: evento_id vacío.');
            }
            if (isset($seen[$eid])) {
                throw new \RuntimeException('Registro de asistencia compacto inválido: evento_id duplicado dentro del mismo registro.');
            }
            $seen[$eid] = true;
        }
    }

    public static function packedToEvents(array $packed): array
    {
        self::validatePacked($packed);
        $out = [];
        foreach ($packed['evento_id'] as $i => $eid) {
            $out[] = [
                'evento_id' => (string) $eid,
                'id_archivo' => (string) ($packed['id_archivo'][$i] ?? ''),
                'estado_asistencia' => (string) ($packed['estado_asistencia'][$i] ?? ''),
                'observacion' => (string) ($packed['observacion'][$i] ?? ''),
            ];
        }
        return $out;
    }

    public static function expandVirtualRows(Collection $rows): Collection
    {
        return $rows->flatMap(function (PgAsistenciaEvento $row) {
            $packed = self::readPacked($row);
            $events = self::packedToEvents($packed);

            return collect($events)->map(function (array $eventRow) use ($row) {
                $virtual = clone $row;
                $virtual->evento_id = $eventRow['evento_id'];
                $virtual->id_archivo = $eventRow['id_archivo'] !== '' ? $eventRow['id_archivo'] : null;
                $virtual->estado_asistencia = $eventRow['estado_asistencia'];
                $virtual->observacion = $eventRow['observacion'] !== '' ? $eventRow['observacion'] : null;
                return $virtual;
            });
        })->values();
    }

    public static function updatePackedAttendance(
        string $personaId,
        string $fecha,
        string $eventoId,
        string $estadoAsistencia,
        ?string $idArchivo,
        ?string $observacion,
        string $uid = ''
    ): PgAsistenciaEvento {
        $personaId = trim($personaId);
        $fecha = trim($fecha);
        $eventoId = trim($eventoId);

        if ($personaId === '' || $fecha === '' || $eventoId === '') {
            throw new \InvalidArgumentException('persona_id, fecha y evento_id son requeridos.');
        }

        $rows = PgAsistenciaEvento::query()
            ->where('persona_id', $personaId)
            ->whereDate('fecha', $fecha)
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            $row = new PgAsistenciaEvento();
            $row->persona_id = $personaId;
            $row->fecha = $fecha;
            if ($uid !== '') {
                $row->creado_por = $uid;
            }
            $packed = [
                'evento_id' => [],
                'id_archivo' => [],
                'estado_asistencia' => [],
                'observacion' => [],
            ];
        } else {
            [$row, $packed, $extraRows] = self::consolidateRows($rows);
            foreach ($extraRows as $extra) {
                $extra->estado = 'X';
                if ($uid !== '') {
                    $extra->actualizado_por = $uid;
                }
                $extra->save();
            }
        }

        $index = array_search($eventoId, $packed['evento_id'], true);
        if ($index === false) {
            $packed['evento_id'][] = $eventoId;
            $packed['id_archivo'][] = (string) ($idArchivo ?? '');
            $packed['estado_asistencia'][] = $estadoAsistencia;
            $packed['observacion'][] = (string) ($observacion ?? '');
        } else {
            $packed['id_archivo'][$index] = (string) ($idArchivo ?? ($packed['id_archivo'][$index] ?? ''));
            $packed['estado_asistencia'][$index] = $estadoAsistencia;
            $packed['observacion'][$index] = (string) ($observacion ?? ($packed['observacion'][$index] ?? ''));
        }

        self::validatePacked($packed);

        $row->evento_id = self::encodeList($packed['evento_id']);
        $row->id_archivo = self::encodeList($packed['id_archivo']);
        $row->estado_asistencia = self::encodeList($packed['estado_asistencia']);
        $row->observacion = self::encodeList($packed['observacion']);
        $row->estado = null;
        if ($uid !== '') {
            $row->actualizado_por = $uid;
        }
        $row->save();

        return $row;
    }

    private static function consolidateRows($rows): array
    {
        $base = $rows->first();
        $eventsById = [];
        $extraRows = [];

        foreach ($rows as $idx => $row) {
            if ($idx > 0) {
                $extraRows[] = $row;
            }
            $packed = self::readPacked($row);
            foreach (self::packedToEvents($packed) as $event) {
                $eventsById[(string) $event['evento_id']] = $event;
            }
        }

        $merged = [
            'evento_id' => [],
            'id_archivo' => [],
            'estado_asistencia' => [],
            'observacion' => [],
        ];

        foreach ($eventsById as $event) {
            $merged['evento_id'][] = (string) $event['evento_id'];
            $merged['id_archivo'][] = (string) ($event['id_archivo'] ?? '');
            $merged['estado_asistencia'][] = (string) ($event['estado_asistencia'] ?? '');
            $merged['observacion'][] = (string) ($event['observacion'] ?? '');
        }

        self::validatePacked($merged);

        return [$base, $merged, $extraRows];
    }
}
