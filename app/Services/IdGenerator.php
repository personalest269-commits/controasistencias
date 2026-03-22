<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Genera IDs numéricos como string usando el procedimiento sp_f_ultimo (MySQL/MariaDB).
 *
 * - Requiere la tabla pg_control y el procedimiento almacenado sp_f_ultimo.
 * - Si por algún motivo no existen (entornos de pruebas), usa un fallback simple.
 */
class IdGenerator
{
    private static function pad10(string $id): string
    {
        $id = trim($id);
        // Si es numérico, lo devolvemos con 10 dígitos (0000000001).
        if ($id !== '' && preg_match('/^\d+$/', $id)) {
            return str_pad($id, 10, '0', STR_PAD_LEFT);
        }
        return $id;
    }

    /**
     * Obtiene el siguiente ID para un "objeto" en pg_control.
     */
    public static function next(string $objeto, ?string $grupo1 = null, ?string $grupo2 = null): string
    {
        // En este sistema los IDs son VARCHAR(10) con padding (0000000001).
        // Para PostgreSQL generamos el consecutivo usando la tabla pg_control.
        // NOTA: estos defaults coinciden con los usados en los seeds/migraciones.
        $grupo1 = $grupo1 ?? '__';
        $grupo2 = $grupo2 ?? '______';

        try {
            if (DB::getDriverName() === 'mysql') {
                // OUT param usando variable de sesión
                DB::statement('CALL sp_f_ultimo(?, ?, ?, @new)', [
                    $objeto,
                    $grupo1,
                    $grupo2,
                ]);

                $row = DB::selectOne('SELECT @new AS new_id');
                if ($row && isset($row->new_id) && $row->new_id !== null && $row->new_id !== '') {
                    return self::pad10((string) $row->new_id);
                }
            }

            if (DB::getDriverName() === 'pgsql') {
                // Asegura existencia de la fila control
                try {
                    DB::statement(
                        'INSERT INTO pg_control(objeto, grupo1, grupo2, ultimo) VALUES (?, ?, ?, 0) '
                        . 'ON CONFLICT (objeto, grupo1, grupo2) DO NOTHING',
                        [$objeto, $grupo1, $grupo2]
                    );
                } catch (\Throwable $e) {
                    // Si la BD no tiene constraint para ON CONFLICT, lo intentamos sin romper.
                }

                // Incremento atómico con RETURNING
                $row = DB::selectOne(
                    'UPDATE pg_control '
                    . 'SET ultimo = COALESCE(ultimo, 0) + 1 '
                    . 'WHERE objeto = ? AND grupo1 = ? AND grupo2 = ? '
                    . 'RETURNING ultimo',
                    [$objeto, $grupo1, $grupo2]
                );

                if ($row && isset($row->ultimo) && $row->ultimo !== null && $row->ultimo !== '') {
                    return self::pad10((string) $row->ultimo);
                }
            }
        } catch (\Throwable $e) {
            // fallback abajo
        }

        // Fallback: 10 dígitos numéricos pseudo-únicos (solo para no romper entornos sin sp_f_ultimo)
        // Nota: en producción se recomienda usar siempre sp_f_ultimo.
        $rand = (string) random_int(1, 9999999999);
        return self::pad10($rand);
    }

    /**
     * Utilidad: devuelve UUID recortado por si necesitas un ID no numérico.
     */
    public static function uuid10(): string
    {
        return substr(str_replace('-', '', (string) Str::uuid()), 0, 10);
    }
}
