<?php

namespace App\Console\Commands;

use App\Models\PgCierreAsistenciaLog;
use App\Services\CierreAsistenciaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PgCerrarAsistenciaDia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --fecha=YYYY-MM-DD para ejecutar manualmente otro día.
     */
    protected $signature = 'pg:cerrar-asistencia-dia {--fecha=} {--run_by=}';

    protected $description = 'Cierra la asistencia del día (marca faltas F para eventos aplicables sin asistencia A ni justificación).';

    public function handle(): int
    {
        $fecha = (string) ($this->option('fecha') ?: Carbon::now()->toDateString());
        $runBy = (string) ($this->option('run_by') ?: 'cron');

        $log = new PgCierreAsistenciaLog();
        $log->fecha = $fecha;
        $log->started_at = now();
        $log->status = 'RUNNING';
        $log->run_by = $runBy;
        $log->save();

        try {
            $res = CierreAsistenciaService::cerrarDia($fecha);
            $log->finished_at = now();
            $log->status = 'OK';
            $log->message = 'Cierre ejecutado correctamente.';
            $log->total_personas = $res['total_personas'] ?? 0;
            $log->total_eventos = $res['total_eventos'] ?? 0;
            $log->faltas_nuevas = $res['faltas_nuevas'] ?? 0;
            $log->faltas_actualizadas = $res['faltas_actualizadas'] ?? 0;
            $log->save();

            $this->info("OK: fecha={$fecha} personas={$log->total_personas} eventos={$log->total_eventos} faltas_nuevas={$log->faltas_nuevas} faltas_actualizadas={$log->faltas_actualizadas}");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $log->finished_at = now();
            $log->status = 'ERROR';
            $log->message = $e->getMessage();
            $log->save();

            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
