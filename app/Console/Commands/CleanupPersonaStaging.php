<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPersonaStaging extends Command
{
    protected $signature = 'persona-staging:cleanup {--days=7 : Delete staging rows older than N days}';
    protected $description = 'Limpia registros antiguos de pg_persona_stg';

    public function handle(): int
    {
        $days = (int)$this->option('days');
        $deleted = DB::table('pg_persona_stg')
            ->where('created_at', '<', DB::raw("DATE_SUB(NOW(), INTERVAL {$days} DAY)"))
            ->delete();

        $this->info("Registros eliminados de pg_persona_stg: {$deleted} (older than {$days} days)");
        return Command::SUCCESS;
    }
}
