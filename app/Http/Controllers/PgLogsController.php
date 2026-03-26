<?php

namespace App\Http\Controllers;

use App\Models\PgLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PgLogsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        // Symfony 7.4+ deprecates Request::get(). Use query/request bags explicitly.
        $q = trim((string) $request->query('q', ''));
        $level = trim((string) $request->query('level', ''));
        $estado = trim((string) $request->query('estado', '')); // open|resolved|deleted|all
        $from = $request->query('from');
        $to = $request->query('to');

        $query = PgLog::query()->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('message', 'like', "%{$q}%")
                  ->orWhere('exception_class', 'like', "%{$q}%")
                  ->orWhere('file', 'like', "%{$q}%")
                  ->orWhere('url', 'like', "%{$q}%")
                  ->orWhere('usuario_id', 'like', "%{$q}%");
            });
        }

        if ($level !== '') {
            $query->where('level', $level);
        }

        if ($estado === 'open') {
            $query->whereNull('estado');
        } elseif ($estado === 'resolved') {
            $query->where('estado', 'R');
        } elseif ($estado === 'deleted') {
            $query->where('estado', 'X');
        } elseif ($estado === 'all') {
            // no filter
        } else {
            // Por defecto: solo abiertos + resueltos (no mostrar eliminados)
            $query->where(function ($w) {
                $w->whereNull('estado')->orWhere('estado', 'R');
            });
        }

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(30)->appends($request->query());

        // Resumen rápido
        $base = PgLog::query();
        $counts = [
            'open' => (clone $base)->whereNull('estado')->count(),
            'resolved' => (clone $base)->where('estado', 'R')->count(),
            'deleted' => (clone $base)->where('estado', 'X')->count(),
            'error' => (clone $base)->where('level', 'error')->count(),
            'warning' => (clone $base)->where('level', 'warning')->count(),
            'critical' => (clone $base)->where('level', 'critical')->count(),
        ];

        $levels = PgLog::query()->select('level')->distinct()->pluck('level')->filter()->values()->toArray();
        sort($levels);

        return view('PgLogs.index', [
            'logs' => $logs,
            'q' => $q,
            'level' => $level,
            'estado' => $estado,
            'from' => $from,
            'to' => $to,
            'counts' => $counts,
            'levels' => $levels,
        ]);
    }

    public function Errors(Request $request)
    {
        $query = array_merge($request->query(), [
            'level' => 'error',
            'estado' => $request->query('estado', 'open'),
        ]);

        return redirect()->route('PgLogsIndex', $query);
    }

    public function Show($id)
    {
        $log = PgLog::with('usuario')->where('id', $id)->firstOrFail();

        return view('PgLogs.show', [
            'log' => $log,
        ]);
    }

    /**
     * Marcar como resuelto / reabrir.
     */
    public function Resolve($id)
    {
        $log = PgLog::where('id', $id)->firstOrFail();

        if ($log->estado === 'R') {
            $log->estado = null;
            $log->resolved_at = null;
            $log->resolved_by = null;
        } else {
            $log->estado = 'R';
            $log->resolved_at = now();
            $log->resolved_by = Auth::check() ? (string) Auth::id() : null;
        }

        $log->save();

        return redirect()->route('PgLogsShow', $log->id)
            ->with('success', $log->estado === 'R' ? 'Marcado como resuelto.' : 'Reabierto (estado activo).');
    }

    /**
     * Eliminación lógica.
     */
    public function Delete($id)
    {
        $log = PgLog::where('id', $id)->firstOrFail();
        $log->estado = 'X';
        $log->save();

        return redirect()->route('PgLogsIndex')->with('success', 'Log marcado como eliminado (X).');
    }
}
