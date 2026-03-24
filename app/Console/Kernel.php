<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\MakeCrud::class,
        Commands\TableMigrationCreator::class,
    
        \App\Console\Commands\CleanupPersonaStaging::class,

        \App\Console\Commands\PgCerrarAsistenciaDia::class,
        \App\Console\Commands\PgMigrarAsistenciaCompacta::class,
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Cierre automático de asistencia todos los días a las 20:00
        $schedule->command('pg:cerrar-asistencia-dia')
            ->dailyAt('20:00')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
