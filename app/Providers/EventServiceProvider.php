<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Log\Events\MessageLogged;
use App\Listeners\StoreLogMessageToDatabase;
use Illuminate\Mail\Events\MessageSending;
use App\Listeners\BlockMailIfDisabled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Guardar Log::error/warning en pg_log (best-effort)
        MessageLogged::class => [
            StoreLogMessageToDatabase::class,
        ],

        // Bloquear emails si CORREO_ACTIVO = N
        MessageSending::class => [
            BlockMailIfDisabled::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
