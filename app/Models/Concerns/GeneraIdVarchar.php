<?php

namespace App\Models\Concerns;

use App\Services\IdGenerator;

/**
 * Asigna automáticamente el campo PK (por defecto: id) cuando la tabla usa
 * IDs VARCHAR (sin auto-increment).
 *
 * Para usarlo:
 * - En tu modelo: use GeneraIdVarchar;
 * - Definir: public const OBJETO_CONTROL = 'NOMBRE_EN_PG_CONTROL';
 */
trait GeneraIdVarchar
{
    protected static function bootGeneraIdVarchar(): void
    {
        static::creating(function ($model) {
            $keyName = $model->getKeyName();
            $current = $model->getAttribute($keyName);

            if (!empty($current)) {
                return;
            }

            $objeto = null;
            if (defined(static::class . '::OBJETO_CONTROL')) {
                $objeto = constant(static::class . '::OBJETO_CONTROL');
            }

            if (!$objeto) {
                return;
            }

            $model->setAttribute($keyName, IdGenerator::next((string) $objeto));
        });
    }
}
