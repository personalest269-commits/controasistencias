<?php

namespace App\Services;

use App\Models\PgConfiguracion;

class AttendanceModeService
{
    public const MODE_SINGLE_CHECK = 'single_check';
    public const MODE_DUAL_CHECK = 'dual_check';

    public static function mode(): string
    {
        $raw = strtolower(trim((string) PgConfiguracion::valor('ASISTENCIA_MODO_REGISTRO', self::MODE_SINGLE_CHECK)));

        if (!in_array($raw, [self::MODE_SINGLE_CHECK, self::MODE_DUAL_CHECK], true)) {
            return self::MODE_SINGLE_CHECK;
        }

        return $raw;
    }

    public static function usesDualCheck(): bool
    {
        return self::mode() === self::MODE_DUAL_CHECK;
    }

    public static function legend(): array
    {
        if (self::usesDualCheck()) {
            return [
                'A' => 'Asistencia completa (inicio y fin)',
                'AI' => 'Asistencia incompleta (solo inicio o solo fin)',
                'F' => 'Falta',
            ];
        }

        return [
            'A' => 'Asistencia',
            'F' => 'Falta',
        ];
    }

    public static function resolveStatusFromChecks(bool $checkInicio, bool $checkFin): string
    {
        if (self::usesDualCheck()) {
            if ($checkInicio && $checkFin) {
                return 'A';
            }

            if ($checkInicio || $checkFin) {
                return 'AI';
            }

            return 'F';
        }

        return $checkFin ? 'A' : 'F';
    }
}
