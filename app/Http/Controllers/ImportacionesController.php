<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportacionesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $estado = trim((string) $request->query('estado', ''));
        $fuente = trim((string) $request->query('fuente', ''));

        $query = DB::table('pg_importacion_batches')->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('batch_id', 'like', "%{$q}%")
                  ->orWhere('archivo_nombre', 'like', "%{$q}%")
                  ->orWhere('api_url', 'like', "%{$q}%");
            });
        }
        if ($estado !== '') $query->where('estado', $estado);
        if ($fuente !== '') $query->where('fuente', $fuente);

        $batches = $query->paginate(20)->appends($request->query());

        return view('Importaciones.index', [
            'batches' => $batches,
            'q' => $q,
            'estado' => $estado,
            'fuente' => $fuente,
        ]);
    }

    public function show(string $batch)
    {
        $batchRow = DB::table('pg_importacion_batches')->where('batch_id', $batch)->first();
        if (!$batchRow) abort(404);

        $logs = DB::table('pg_importacion_logs')
            ->where('batch_id', $batch)
            ->orderByDesc('id')
            ->paginate(30);

        return view('Importaciones.show', [
            'batch' => $batchRow,
            'logs' => $logs,
        ]);
    }

    // Gestión Log (vista general de logs)
    public function logs(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $accion = trim((string) $request->query('accion', ''));
        $batch = trim((string) $request->query('batch', ''));

        $query = DB::table('pg_importacion_logs')->orderByDesc('id');

        if ($batch !== '') $query->where('batch_id', $batch);
        if ($accion !== '') $query->where('accion', $accion);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('identificacion', 'like', "%{$q}%")
                  ->orWhere('mensaje_error', 'like', "%{$q}%")
                  ->orWhere('batch_id', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(30)->appends($request->query());

        return view('Importaciones.logs', [
            'logs' => $logs,
            'q' => $q,
            'accion' => $accion,
            'batch' => $batch,
        ]);
    }
}
