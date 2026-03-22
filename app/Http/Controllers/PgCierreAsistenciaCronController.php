<?php

namespace App\Http\Controllers;

use App\Models\PgCierreAsistenciaLog;
use App\Services\CierreAsistenciaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PgCierreAsistenciaCronController extends Controller
{
    public function Index(Request $request)
    {
        $fecha = (string) ($request->input('fecha') ?: Carbon::now()->toDateString());
        $logs = PgCierreAsistenciaLog::query()->orderByDesc('id')->limit(100)->get();

        return view('PgCierreAsistenciaCron.index', [
            'fecha' => $fecha,
            'logs' => $logs,
            'horaProgramada' => '20:00',
        ]);
    }

    public function Ejecutar(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
        ]);

        $fecha = (string) $request->input('fecha');
        $uid = (string) (Auth::user()->id ?? '');

        $log = new PgCierreAsistenciaLog();
        $log->fecha = $fecha;
        $log->started_at = now();
        $log->status = 'RUNNING';
        $log->run_by = $uid !== '' ? ('user:' . $uid) : 'user';
        $log->save();

        try {
            $res = CierreAsistenciaService::cerrarDia($fecha, null, $uid);
            $log->finished_at = now();
            $log->status = 'OK';
            $log->message = 'Cierre ejecutado manualmente.';
            $log->total_personas = $res['total_personas'] ?? 0;
            $log->total_eventos = $res['total_eventos'] ?? 0;
            $log->faltas_nuevas = $res['faltas_nuevas'] ?? 0;
            $log->faltas_actualizadas = $res['faltas_actualizadas'] ?? 0;
            $log->save();

            return redirect()->route('PgCierreAsistenciaCronIndex', ['fecha' => $fecha])
                ->with('success', 'Cierre ejecutado. Revisar logs abajo.');
        } catch (\Throwable $e) {
            $log->finished_at = now();
            $log->status = 'ERROR';
            $log->message = $e->getMessage();
            $log->save();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
