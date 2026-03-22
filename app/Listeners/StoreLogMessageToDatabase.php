<?php

namespace App\Listeners;

use App\Services\PgLogService;
use Illuminate\Log\Events\MessageLogged;
use Throwable;

class StoreLogMessageToDatabase
{
    /**
     * Handle the event.
     */
    public function handle(MessageLogged $event): void
    {
        try {
            // Si el log ya trae una excepción, la capturamos desde Handler y
            // evitamos duplicar.
            if (isset($event->context['exception']) && $event->context['exception'] instanceof Throwable) {
                return;
            }

            $level = (string) ($event->level ?? 'error');

            // Guardar niveles relevantes. (Si quieres TODO, quita esta condición.)
            if (!in_array($level, ['error', 'critical', 'alert', 'emergency', 'warning'], true)) {
                return;
            }

            PgLogService::captureMessage($level, (string) $event->message, (string) ($event->channel ?? 'app'), (array) ($event->context ?? []));
        } catch (Throwable $ignore) {
            // silent
        }
    }
}
