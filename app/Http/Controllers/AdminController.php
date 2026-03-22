<?php
namespace App\Http\Controllers;

use App\Models\User;
Use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Models\Widgets;
use App\Models\PgPersona;
use App\Models\PgEvento;
use App\Models\PgAsistenciaEvento;
use App\Models\PgJustificacionAsistencia;
use DB;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

Class AdminController extends Controller
{

    public $widgets=['sum_count_avg'=>[],'group'=>[]];
    function __construct()
    {
        parent::__construct();
    }
    
    function DashBoard()
    {
        // Totales principales (solo activos; EstadoSoftDeletes aplica global scope)
        $totalUsuarios = User::count();
        $totalPersonas = PgPersona::count();
        $totalEventos  = PgEvento::count();

        // Solo asistencias activas marcadas (A=Asistió, T=Tarde)
        $totalAsistidos = PgAsistenciaEvento::query()
            ->whereIn('estado_asistencia', ['A', 'T'])
            ->count();

        // Justificaciones (todas las activas)
        $totalJustificados = PgJustificacionAsistencia::count();

        // Estimar "no asistidos" = asistencias esperadas - asistidos - justificados
        // (Para eventos hasta hoy. Si un evento es global, aplica a todas las personas.)
        $hoy = Carbon::today();
        $eventosHastaHoy = PgEvento::query()->whereDate('fecha_inicio', '<=', $hoy)->get(['id']);

        $personasPorDepto = null; // lazy
        $totalEsperados = 0;

        foreach ($eventosHastaHoy as $ev) {
            $eventoId = (string) $ev->id;

            $personaIds = DB::table('pg_evento_persona')
                ->where('evento_id', $eventoId)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->pluck('persona_id')
                ->map(fn($v) => (string) $v)
                ->toArray();

            $deptoIds = DB::table('pg_evento_departamento')
                ->where('evento_id', $eventoId)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->pluck('departamento_id')
                ->map(fn($v) => (string) $v)
                ->toArray();

            // Global: sin targets -> todas las personas
            if (empty($personaIds) && empty($deptoIds)) {
                $totalEsperados += $totalPersonas;
                continue;
            }

            // Si tiene deptos, sumar personas de esos deptos (evitando duplicados con personas directas)
            if (!empty($deptoIds)) {
                if ($personasPorDepto === null) {
                    $personasPorDepto = DB::table('pg_persona')
                        ->select('id', 'departamento_id')
                        ->where(function ($q) {
                            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                        })
                        ->get()
                        ->groupBy('departamento_id')
                        ->map(fn($rows) => $rows->pluck('id')->map(fn($v) => (string) $v)->toArray())
                        ->toArray();
                }
                foreach ($deptoIds as $did) {
                    $personaIds = array_merge($personaIds, $personasPorDepto[$did] ?? []);
                }
            }

            $personaIds = array_values(array_unique(array_filter($personaIds)));
            $totalEsperados += count($personaIds);
        }

        $totalNoAsistidos = max(0, (int) $totalEsperados - (int) $totalAsistidos - (int) $totalJustificados);

        return view('dashboard', [
            'stats' => [
                'personas' => $totalPersonas,
                'usuarios' => $totalUsuarios,
                'eventos' => $totalEventos,
                'asistidos' => $totalAsistidos,
                'no_asistidos' => $totalNoAsistidos,
                'justificados' => $totalJustificados,
            ],
        ]);
    }

    /**
     * Fuente JSON para el calendario del Dashboard (FullCalendar).
     * - Admin / Super-Admin: todos los eventos
     * - Otros: eventos globales + asignados a su persona o a su departamento
     */
    public function dashboardEvents(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([]);
        }

        $isAdmin = false;
        try {
            $isAdmin = $user->hasRole('Super-Admin') || $user->hasRole('Admin');
        } catch (\Throwable $e) {
            $isAdmin = false;
        }

        $q = PgEvento::query();

        if (!$isAdmin) {
            $personaId = (string) ($user->id_persona ?? '');
            $deptoId = (string) optional($user->persona)->departamento_id;

            $q->where(function ($w) use ($personaId, $deptoId) {
                // Global (sin asignación)
                $w->whereDoesntHave('personas')->whereDoesntHave('departamentos');

                // Asignado a persona
                if ($personaId !== '') {
                    $w->orWhereHas('personas', function ($p) use ($personaId) {
                        $p->where('pg_persona.id', $personaId);
                    });
                }

                // Asignado a departamento
                if ($deptoId !== '') {
                    $w->orWhereHas('departamentos', function ($d) use ($deptoId) {
                        $d->where('pg_departamentos.id', $deptoId);
                    });
                }
            });
        }

        // Rango opcional que manda FullCalendar
        if ($request->filled('start')) {
            try {
                $q->whereDate('fecha_fin', '>=', Carbon::parse($request->get('start'))->toDateString());
            } catch (\Throwable $e) {
            }
        }
        if ($request->filled('end')) {
            try {
                $q->whereDate('fecha_inicio', '<=', Carbon::parse($request->get('end'))->toDateString());
            } catch (\Throwable $e) {
            }
        }

        $events = $q->orderBy('fecha_inicio')->get(['id', 'titulo', 'fecha_inicio', 'fecha_fin', 'color']);

        $out = $events->map(function ($e) {
            $start = $e->fecha_inicio ? $e->fecha_inicio->format('Y-m-d') : null;
            $endBase = $e->fecha_fin ?: $e->fecha_inicio;
            $end = $endBase ? $endBase->copy()->addDay()->format('Y-m-d') : null; // FullCalendar end exclusivo

            return [
                'id' => (string) $e->id,
                'title' => (string) $e->titulo,
                'start' => $start,
                'end' => $end,
                'backgroundColor' => $e->color ?: null,
                'borderColor' => $e->color ?: null,
            ];
        })->values();

        return response()->json($out);
    }

    public function getWidgets(){
        $allWidgets=Widgets::all();
        foreach($allWidgets as $widget){
            switch($widget->type){
                case 'average':
                    $widget->value=ceil(DB::table($widget->table)->avg($widget->tablefield));
                    array_push($this->widgets['sum_count_avg'],$widget);
                break;
                case 'count':
                    $widget->value=DB::table($widget->table)->count($widget->tablefield);
                    array_push($this->widgets['sum_count_avg'],$widget);
                break;
                case 'sum':
                    $widget->value=DB::table($widget->table)->sum($widget->tablefield);
                    array_push($this->widgets['sum_count_avg'],$widget);
                break;
                case 'group':
                    $widget->value=DB::table($widget->table)->select('id',$widget->tablefield,DB::raw('count(*) as total'),DB::raw('round(count('.$widget->tablefield.')/(select count(id) from '.$widget->table.')*100) as percentage'))->groupBy($widget->tablefield)->get();
                    array_push($this->widgets['group'],$widget);
                break;
            }
  
        }
        $this->widgets['colors']=array('dark','aero','green','blue','red','purple');
        //var_dump($this->widgets['sum_count_avg'][0]);die();
        return $this->widgets;
    }
    
    
    public function FileManage()
    {
        return view('filemanage');
    }
}

?>
