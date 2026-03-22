<?php

namespace App\Listeners;

use App\Models\PgConfiguracion;
use Illuminate\Mail\Events\MessageSending;

/**
 * Cancela el envío de correos si CORREO_ACTIVO = N.
 *
 * Nota: En Laravel, retornar false en MessageSending evita el envío.
 */
class BlockMailIfDisabled
{
    public function handle(MessageSending $event)
    {
        try {
            if (!PgConfiguracion::bool('CORREO_ACTIVO', true)) {
                return false;
            }
        } catch (\Throwable $e) {
            // si falla lectura de config, no bloquear.
        }

        return null;
    }
}
