<?php

namespace App\Http\Controllers;

use App\Models\PgAsistenciaEvento;
use App\Models\PgAsistenciaLote;
use App\Models\PgAsistenciaLoteArchivo;
use App\Models\PgDepartamento;
use App\Models\PgEmpresa;
use App\Models\PgEvento;
use App\Models\PgJustificacionAsistencia;
use App\Models\PgPersona;
use App\Services\ArchivoDigitalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PgAsistenciasController extends Controller
{
    /**
     * Normaliza fecha recibida desde UI/URL.
     * Acepta formatos: Y-m-d o d/m/Y y devuelve Y-m-d.
     */
    private function normalizarFecha(?string $fechaRaw): ?string
    {
        $fechaRaw = trim((string) $fechaRaw);
        if ($fechaRaw === '') {
            return null;
        }

        // 1) Formato ISO
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRaw)) {
                return Carbon::createFromFormat('Y-m-d', $fechaRaw)->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            // continúa
        }

        // 2) Formato UI (dd/mm/yyyy)
        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaRaw)) {
                return Carbon::createFromFormat('d/m/Y', $fechaRaw)->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            // continúa
        }

        return null;
    }
    /**
     * Cierra asistencia del día:
     * - Mantiene las marcadas como Asistió (A)
     * - Registra Faltas (F) para (persona, evento) aplicables sin A y sin Justificación aprobada.
     *
     * Puede ejecutarse para todo el filtro (fecha+depto) o para una persona (AJAX).
     */
    public function CerrarDia(Request $request)
    {
        $request->validate([
            // Puede venir como Y-m-d (URL) o d/m/Y (UI)
            'fecha' => 'required',
            'departamento_id' => 'nullable|string',
            'persona_id' => 'nullable|string',
        ]);

        $fecha = $this->normalizarFecha((string) $request->input('fecha'));
        if (!$fecha) {
            $msg = 'Formato de fecha inválido.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }
        $departamentoId = trim((string) $request->input('departamento_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }
        $personaId = trim((string) $request->input('persona_id'));
        if ($personaId === '') {
            $personaId = null;
        }

        $uid = (string) (Auth::user()->id ?? '');

        // Validación: si no hay eventos creados para la fecha, no se puede cerrar asistencia
        // Validación robusta: evento cruza el día seleccionado
        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';

        $eventsCount = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->count();
        if ($eventsCount === 0) {
            $msg = 'No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        DB::beginTransaction();
        try {
            $this->rellenarFaltas($fecha, $departamentoId, $personaId ? [$personaId] : null, $uid);
            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true]);
            }

            return redirect()->route('PgAsistenciasIndex', ['fecha' => $fecha, 'departamento_id' => $departamentoId])
                ->with('success', 'Asistencia del día cerrada. Faltas registradas.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function Index(Request $request)
    {
        $fecha = $this->normalizarFecha((string) $request->input('fecha')) ?: Carbon::today()->format('Y-m-d');
        $departamentoId = trim((string) $request->input('departamento_id'));
        $personaQ = trim((string) $request->input('persona_q'));
        $personaId = trim((string) $request->input('persona_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }
        if ($personaId === '') {
            $personaId = null;
        }

        $departamentos = PgDepartamento::orderBy('descripcion')->get();

        $personasQ = PgPersona::query()->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) {
            $personasQ->where('departamento_id', $departamentoId);
        }
        if ($personaQ !== '') {
            $like = '%' . str_replace(' ', '%', $personaQ) . '%';
            $personasQ->where(function ($q) use ($like) {
                $q->where('identificacion', 'like', $like)
                  ->orWhere('nombres', 'like', $like)
                  ->orWhere('apellido1', 'like', $like)
                  ->orWhere('apellido2', 'like', $like)
                  ->orWhere(DB::raw("CONCAT(COALESCE(apellido1,''),' ',COALESCE(apellido2,''),' ',COALESCE(nombres,''))"), 'like', $like);
            });
        }
        if ($personaId) {
            $personasQ->where('id', $personaId);
        }
        $personas = $personasQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';

        $events = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->orderBy('fecha_inicio')
            ->get();

        $eventTargets = $this->loadEventTargets($events->pluck('id')->all());

        // Personas por departamento para el caso: evento solo a personas (y se filtra por depto)
        $personaDeptMap = $personas->pluck('departamento_id', 'id')->all();

        $eventsByPerson = [];
        $applicableEventIdsByPerson = [];

        foreach ($personas as $p) {
            $list = [];
            $ids = [];
            foreach ($events as $e) {
                if ($this->eventAppliesToPerson($e->id, $p->id, $p->departamento_id, $eventTargets)) {
                    $list[] = $e;
                    $ids[] = $e->id;
                }
            }
            $eventsByPerson[$p->id] = $list;
            $applicableEventIdsByPerson[$p->id] = $ids;
        }

        // Asistencias ya registradas
        $asistencias = PgAsistenciaEvento::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('persona_id', $personas->pluck('id')->all())
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();

        $asistenciaMap = [];
        foreach ($asistencias as $a) {
            $asistenciaMap[$a->persona_id][$a->evento_id] = $a;
        }

        // Justificaciones aprobadas
        $justificaciones = PgJustificacionAsistencia::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('persona_id', $personas->pluck('id')->all())
            ->where('estado_revision', 'A')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();
        $justMap = [];
        foreach ($justificaciones as $j) {
            $justMap[$j->persona_id][$j->evento_id] = $j;
        }

        // Selección inicial:
        // - Si hay registros de asistencia -> respetarlos.
        // - Si NO hay registros y estamos en modo depto -> seleccionar TODOS los eventos aplicables.
        $selectedByPerson = [];
        foreach ($personas as $p) {
            $existing = [];
            foreach (($asistenciaMap[$p->id] ?? []) as $eid => $row) {
                if (($row->estado_asistencia ?? null) === 'A') {
                    $existing[] = (string) $eid;
                }
            }
            if (!empty($existing)) {
                $selectedByPerson[$p->id] = $existing;
            } else {
                $selectedByPerson[$p->id] = $departamentoId ? ($applicableEventIdsByPerson[$p->id] ?? []) : [];
            }
        }

        // Eventos para evidencias por departamento (una vez por evento) + archivos existentes
        $deptEventRows = [];
        if ($departamentoId) {
            foreach ($events as $e) {
                // Mostrar si aplica al depto o si al menos un empleado del depto está en lista de personas del evento
                $appliesToDept = $this->eventAppliesToDepartment($e->id, $departamentoId, $eventTargets, $personaDeptMap);
                if (!$appliesToDept) {
                    continue;
                }

                $lote = PgAsistenciaLote::query()
                    ->where('evento_id', $e->id)
                    ->where('departamento_id', $departamentoId)
                    ->whereDate('fecha', $fecha)
                    ->where(function ($q) {
                        $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                    })
                    ->first();

                $archivosCount = 0;
                if ($lote) {
                    $archivosCount = PgAsistenciaLoteArchivo::query()
                        ->where('asistencia_lote_id', $lote->id)
                        ->where(function ($q) {
                            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                        })
                        ->count();
                }

                $deptEventRows[] = [
                    'evento' => $e,
                    'lote' => $lote,
                    'archivos_count' => $archivosCount,
                ];
            }
        }

        return view('PgAsistencias.index', [
            'fecha' => $fecha,
            'departamentoId' => $departamentoId,
            'personaQ' => $personaQ,
            'personaId' => $personaId,
            'departamentos' => $departamentos,
            'personas' => $personas,
            // IMPORTANT: la vista usa $events para mostrar/ocultar el aviso y habilitar/deshabilitar acciones.
            // Si no se envía, la vista asume 0 eventos y mantiene el bloqueo aunque existan en BD.
            'events' => $events,
            'eventsByPerson' => $eventsByPerson,
            'selectedByPerson' => $selectedByPerson,
            'asistenciaMap' => $asistenciaMap,
            'justMap' => $justMap,
            'deptEventRows' => $deptEventRows,
        ]);
    }

    // ==========================================================
    // NUEVO: Asistencia masiva por EMPRESA
    // ==========================================================

    public function IndexEmpresa(Request $request)
    {
        $fecha = $this->normalizarFecha((string) $request->input('fecha')) ?: Carbon::today()->format('Y-m-d');
        $empresaId = trim((string) $request->input('empresa_id'));
        $personaId = trim((string) $request->input('persona_id'));
        if ($empresaId === '') {
            $empresaId = null;
        }
        if ($personaId === '') {
            $personaId = null;
        }

        $empresas = PgEmpresa::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('nombre')
            ->get();

        $departamentos = PgDepartamento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('descripcion')
            ->get();

        $deptIds = [];
        if ($empresaId) {
            $deptIds = $departamentos->where('empresa_id', $empresaId)->pluck('id')->all();
        }

        $personasQ = PgPersona::query()->with('departamento')->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($empresaId) {
            if (empty($deptIds)) {
                $personasQ->whereRaw('1=0');
            } else {
                $personasQ->whereIn('departamento_id', $deptIds);
            }
        }
        if ($personaId) {
            $personasQ->where('id', $personaId);
        }

        $personas = $personasQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';

        $events = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->orderBy('fecha_inicio')
            ->get();

        $eventTargets = $this->loadEventTargets($events->pluck('id')->all());
        $personaDeptMap = $personas->pluck('departamento_id', 'id')->all();

        $eventsByPerson = [];
        $applicableEventIdsByPerson = [];
        foreach ($personas as $p) {
            $list = [];
            $ids = [];
            foreach ($events as $e) {
                if ($this->eventAppliesToPerson($e->id, $p->id, $p->departamento_id, $eventTargets)) {
                    $list[] = $e;
                    $ids[] = $e->id;
                }
            }
            $eventsByPerson[$p->id] = $list;
            $applicableEventIdsByPerson[$p->id] = $ids;
        }

        $asistencias = PgAsistenciaEvento::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('persona_id', $personas->pluck('id')->all())
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();

        $asistenciaMap = [];
        foreach ($asistencias as $a) {
            $asistenciaMap[$a->persona_id][$a->evento_id] = $a;
        }

        $justificaciones = PgJustificacionAsistencia::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('persona_id', $personas->pluck('id')->all())
            ->where('estado_revision', 'A')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();
        $justMap = [];
        foreach ($justificaciones as $j) {
            $justMap[$j->persona_id][$j->evento_id] = $j;
        }

        $selectedByPerson = [];
        foreach ($personas as $p) {
            $existing = [];
            foreach (($asistenciaMap[$p->id] ?? []) as $eid => $row) {
                if (($row->estado_asistencia ?? null) === 'A') {
                    $existing[] = (string) $eid;
                }
            }
            if (!empty($existing)) {
                $selectedByPerson[$p->id] = $existing;
            } else {
                $selectedByPerson[$p->id] = $empresaId ? ($applicableEventIdsByPerson[$p->id] ?? []) : [];
            }
        }

        return view('PgAsistenciasEmpresa.index', [
            'fecha' => $fecha,
            'empresaId' => $empresaId,
            'personaId' => $personaId,
            'empresas' => $empresas,
            'personas' => $personas,
            'events' => $events,
            'eventsByPerson' => $eventsByPerson,
            'selectedByPerson' => $selectedByPerson,
            'asistenciaMap' => $asistenciaMap,
            'justMap' => $justMap,
        ]);
    }

    public function PersonasSearchEmpresa(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $empresaId = trim((string) $request->input('empresa_id'));
        if ($empresaId === '') {
            $empresaId = null;
        }

        $deptIds = [];
        if ($empresaId) {
            $deptIds = PgDepartamento::query()
                ->where('empresa_id', $empresaId)
                ->where(function ($x) {
                    $x->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->pluck('id')
                ->all();
        }

        $query = PgPersona::query()
            ->select(['id', 'identificacion', 'nombres', 'apellido1', 'apellido2', 'departamento_id'])
            ->where(function ($x) {
                $x->whereNull('estado')->orWhere('estado', '<>', 'X');
            });

        if ($empresaId) {
            if (empty($deptIds)) {
                $query->whereRaw('1=0');
            } else {
                $query->whereIn('departamento_id', $deptIds);
            }
        }

        if ($q !== '') {
            $like = '%' . str_replace(' ', '%', $q) . '%';
            $query->where(function ($x) use ($like) {
                $x->where('identificacion', 'like', $like)
                    ->orWhere('nombres', 'like', $like)
                    ->orWhere('apellido1', 'like', $like)
                    ->orWhere('apellido2', 'like', $like)
                    ->orWhere(DB::raw("CONCAT(COALESCE(apellido1,''),' ',COALESCE(apellido2,''),' ',COALESCE(nombres,''))"), 'like', $like);
            });
        }

        $items = $query
            ->orderBy('apellido1')
            ->orderBy('apellido2')
            ->orderBy('nombres')
            ->limit(20)
            ->get();

        $results = $items->map(function ($p) {
            $name = trim(($p->apellido1 ?? '') . ' ' . ($p->apellido2 ?? '') . ' ' . ($p->nombres ?? ''));
            $text = ($p->identificacion ? ($p->identificacion . ' — ') : '') . $name;
            return ['id' => (string) $p->id, 'text' => $text];
        })->all();

        return response()->json(['results' => $results]);
    }

    public function ActualizarEmpresa(Request $request)
    {
        // Reutiliza la lógica general (sin lote por departamento)
        $fecha = $this->normalizarFecha((string) $request->input('fecha'));
        if (!$fecha) {
            return redirect()->back()->with('error', 'Debe seleccionar una fecha.');
        }

        $empresaId = trim((string) $request->input('empresa_id'));
        if ($empresaId === '') {
            $empresaId = null;
        }

        // Forzamos modo general
        $request->merge(['departamento_id' => null]);

        // Ejecutamos la misma rutina que Actualizar, pero redirigimos a la pantalla Empresa
        $personEvents = $request->input('person_events', []);
        if (!is_array($personEvents)) {
            $personEvents = [];
        }

        $uid = (string) (Auth::user()->id ?? '');

        // Validación eventos del día
        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';
        $eventsCount = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->count();
        if ($eventsCount === 0) {
            return redirect()->back()->with('error', 'No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.');
        }

        $autoClose = $request->has('auto_close') && ((string) $request->input('auto_close') === '1');
        $cerrarDia = $request->has('cerrar_dia') && ((string) $request->input('cerrar_dia') === '1');

        DB::beginTransaction();
        try {
            foreach ($personEvents as $personaId => $selected) {
                $personaId = (string) $personaId;
                $selected = array_values(array_unique(array_filter((array) $selected)));

                $filePersona = $request->file("person_file.$personaId");
                $idArchivoPersona = null;
                if ($filePersona) {
                    $idArchivoPersona = ArchivoDigitalService::store($filePersona, 'Evidencia asistencia persona ' . $personaId);
                }

                $existingRows = PgAsistenciaEvento::query()
                    ->where('persona_id', $personaId)
                    ->whereDate('fecha', $fecha)
                    ->where(function ($q) {
                        $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                    })
                    ->get();
                $existingByEvento = $existingRows->keyBy('evento_id');

                foreach ($selected as $eventoId) {
                    $eventoId = (string) $eventoId;

                    $row = $existingByEvento->get($eventoId);
                    if (!$row) {
                        $row = new PgAsistenciaEvento();
                        $row->evento_id = $eventoId;
                        $row->persona_id = $personaId;
                        $row->fecha = $fecha;
                        if ($uid !== '') {
                            $row->creado_por = $uid;
                        }
                    }

                    $row->asistencia_lote_id = null;
                    if ($idArchivoPersona) {
                        $row->id_archivo = $idArchivoPersona;
                    }
                    $row->estado_asistencia = 'A';
                    $row->estado = null;
                    if ($uid !== '') {
                        $row->actualizado_por = $uid;
                    }
                    $row->save();
                }

                foreach ($existingByEvento as $eventoId => $row) {
                    if (!in_array($eventoId, $selected, true)) {
                        $row->estado_asistencia = 'F';
                        $row->estado = null;
                        if ($uid !== '') {
                            $row->actualizado_por = $uid;
                        }
                        $row->save();
                    }
                }
            }

            if ($autoClose || $cerrarDia) {
                $personaIds = array_keys($personEvents);
                $this->rellenarFaltas($fecha, null, $personaIds, $uid);
            }

            DB::commit();
            return redirect()->route('PgAsistenciasEmpresaIndex', ['fecha' => $fecha, 'empresa_id' => $empresaId])
                ->with('success', 'Asistencias actualizadas correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function ActualizarItemEmpresa(Request $request)
    {
        // Igual que ActualizarItem, pero en modo empresa (sin lote por departamento)
        $request->validate([
            'fecha' => 'required',
            'persona_id' => 'required|string',
            'empresa_id' => 'nullable|string',
            'eventos' => 'array',
            'auto_close' => 'nullable|in:0,1',
        ]);

        $fecha = $this->normalizarFecha((string) $request->input('fecha'));
        if (!$fecha) {
            return response()->json(['ok' => false, 'message' => 'Formato de fecha inválido.'], 422);
        }
        $personaId = (string) $request->input('persona_id');
        $selected = array_values(array_unique(array_filter((array) $request->input('eventos', []))));

        $uid = (string) (Auth::user()->id ?? '');

        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';
        $eventsCount = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->count();
        if ($eventsCount === 0) {
            return response()->json([
                'ok' => false,
                'message' => 'No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $existingRows = PgAsistenciaEvento::query()
                ->where('persona_id', $personaId)
                ->whereDate('fecha', $fecha)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->get();
            $existingByEvento = $existingRows->keyBy('evento_id');

            foreach ($selected as $eventoId) {
                $eventoId = (string) $eventoId;
                $row = $existingByEvento->get($eventoId);
                if (!$row) {
                    $row = new PgAsistenciaEvento();
                    $row->evento_id = $eventoId;
                    $row->persona_id = $personaId;
                    $row->fecha = $fecha;
                    if ($uid !== '') {
                        $row->creado_por = $uid;
                    }
                }

                $row->asistencia_lote_id = null;
                $row->estado_asistencia = 'A';
                $row->estado = null;
                if ($uid !== '') {
                    $row->actualizado_por = $uid;
                }
                $row->save();
            }

            foreach ($existingByEvento as $eventoId => $row) {
                if (!in_array($eventoId, $selected, true)) {
                    $row->estado_asistencia = 'F';
                    $row->estado = null;
                    if ($uid !== '') {
                        $row->actualizado_por = $uid;
                    }
                    $row->save();
                }
            }

            if ((string) $request->input('auto_close') === '1') {
                $this->rellenarFaltas($fecha, null, [$personaId], $uid);
            }

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function CerrarDiaEmpresa(Request $request)
    {
        $request->validate([
            'fecha' => 'required',
            'empresa_id' => 'nullable|string',
            'persona_id' => 'nullable|string',
        ]);

        $fecha = $this->normalizarFecha((string) $request->input('fecha'));
        if (!$fecha) {
            $msg = 'Formato de fecha inválido.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $empresaId = trim((string) $request->input('empresa_id'));
        if ($empresaId === '') {
            $empresaId = null;
        }
        $personaId = trim((string) $request->input('persona_id'));
        if ($personaId === '') {
            $personaId = null;
        }

        $uid = (string) (Auth::user()->id ?? '');

        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';

        $eventsCount = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->count();
        if ($eventsCount === 0) {
            $msg = 'No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Personas objetivo
        $personaIds = null;
        if ($personaId) {
            $personaIds = [$personaId];
        } elseif ($empresaId) {
            $deptIds = PgDepartamento::query()
                ->where('empresa_id', $empresaId)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->pluck('id')
                ->all();

            $personaIds = PgPersona::query()
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->whereIn('departamento_id', $deptIds)
                ->pluck('id')
                ->all();
        }

        DB::beginTransaction();
        try {
            $this->rellenarFaltas($fecha, null, $personaIds, $uid);
            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true]);
            }

            return redirect()->route('PgAsistenciasEmpresaIndex', ['fecha' => $fecha, 'empresa_id' => $empresaId])
                ->with('success', 'Asistencia del día cerrada. Faltas registradas.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Select2: buscar personas por cédula/identificación o nombre.
     */
    public function PersonasSearch(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $departamentoId = trim((string) $request->input('departamento_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }

        $query = PgPersona::query()
            ->select(['id', 'identificacion', 'nombres', 'apellido1', 'apellido2'])
            ->where(function ($x) {
                $x->whereNull('estado')->orWhere('estado', '<>', 'X');
            });
        if ($departamentoId) {
            $query->where('departamento_id', $departamentoId);
        }
        if ($q !== '') {
            $like = '%' . str_replace(' ', '%', $q) . '%';
            $query->where(function ($x) use ($like) {
                $x->where('identificacion', 'like', $like)
                    ->orWhere('nombres', 'like', $like)
                    ->orWhere('apellido1', 'like', $like)
                    ->orWhere('apellido2', 'like', $like)
                    ->orWhere(DB::raw("CONCAT(COALESCE(apellido1,''),' ',COALESCE(apellido2,''),' ',COALESCE(nombres,''))"), 'like', $like);
            });
        }

        $items = $query
            ->orderBy('apellido1')
            ->orderBy('apellido2')
            ->orderBy('nombres')
            ->limit(20)
            ->get();

        $results = $items->map(function ($p) {
            $name = trim(($p->apellido1 ?? '') . ' ' . ($p->apellido2 ?? '') . ' ' . ($p->nombres ?? ''));
            $text = ($p->identificacion ? ($p->identificacion . ' — ') : '') . $name;
            return ['id' => (string) $p->id, 'text' => $text];
        })->all();

        return response()->json(['results' => $results]);
    }

    /**
     * Auto-guardado (AJAX) para una persona (NO incluye evidencias/archivos).
     */
    public function ActualizarItem(Request $request)
    {
        $request->validate([
            'fecha' => 'required',
            'persona_id' => 'required|string',
            'departamento_id' => 'nullable|string',
            'eventos' => 'array',
            'auto_close' => 'nullable|in:0,1',
        ]);

        $fecha = $this->normalizarFecha((string) $request->input('fecha'));
        if (!$fecha) {
            return response()->json(['ok' => false, 'message' => 'Formato de fecha inválido.'], 422);
        }
        $personaId = (string) $request->input('persona_id');
        $departamentoId = trim((string) $request->input('departamento_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }
        $selected = array_values(array_unique(array_filter((array) $request->input('eventos', []))));

        $uid = (string) (Auth::user()->id ?? '');

        // Validación: si no hay eventos creados para la fecha, no se puede auto-actualizar
        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';
        $eventsCount = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->count();
        if ($eventsCount === 0) {
            return response()->json([
                'ok' => false,
                'message' => 'No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Asistencias existentes del día para esta persona
            $existingRows = PgAsistenciaEvento::query()
                ->where('persona_id', $personaId)
                ->whereDate('fecha', $fecha)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->get();
            $existingByEvento = $existingRows->keyBy('evento_id');

            // En modo departamento, ligar a lote existente (sin crear ni subir evidencias)
            $loteByEvento = [];
            if ($departamentoId && !empty($selected)) {
                $lotes = PgAsistenciaLote::query()
                    ->where('departamento_id', $departamentoId)
                    ->whereDate('fecha', $fecha)
                    ->whereIn('evento_id', $selected)
                    ->where(function ($q) {
                        $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                    })
                    ->get();
                foreach ($lotes as $l) {
                    $loteByEvento[(string) $l->evento_id] = (string) $l->id;
                }
            }

            // Crear/actualizar seleccionadas
            foreach ($selected as $eventoId) {
                $eventoId = (string) $eventoId;

                $row = $existingByEvento->get($eventoId);
                if (!$row) {
                    $row = new PgAsistenciaEvento();
                    $row->evento_id = $eventoId;
                    $row->persona_id = $personaId;
                    $row->fecha = $fecha;
                    if ($uid !== '') {
                        $row->creado_por = $uid;
                    }
                }

                if ($departamentoId) {
                    $row->asistencia_lote_id = $loteByEvento[$eventoId] ?? $row->asistencia_lote_id;
                    $row->id_archivo = null;
                } else {
                    $row->asistencia_lote_id = null;
                }

                $row->estado_asistencia = 'A';
                $row->estado = null;
                if ($uid !== '') {
                    $row->actualizado_por = $uid;
                }
                $row->save();
            }

            // Eliminar lógicamente las no seleccionadas que existían
            foreach ($existingByEvento as $eventoId => $row) {
                if (!in_array($eventoId, $selected, true)) {
                    // No seleccionada => Falta (F)
                    $row->estado_asistencia = 'F';
                    $row->estado = null;
                    if ($uid !== '') {
                        $row->actualizado_por = $uid;
                    }
                    $row->save();
                }
            }

            // Si viene flag auto_close=1, cerramos faltas para esta persona
            if ((string) $request->input('auto_close') === '1') {
                $this->rellenarFaltas($fecha, $departamentoId, [$personaId], $uid);
            }

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function Actualizar(Request $request)
    {
        $fecha = $this->normalizarFecha((string) $request->input('fecha'));
        if (!$fecha) {
            return redirect()->back()->with('error', 'Debe seleccionar una fecha.');
        }

        $departamentoId = trim((string) $request->input('departamento_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }

        $personEvents = $request->input('person_events', []);
        if (!is_array($personEvents)) {
            $personEvents = [];
        }

        // Si se filtra por depto, validamos max 4 fotos por evento (sumando las existentes)
        $deptEventFiles = $request->file('dept_event_files', []);

        $uid = (string) (Auth::user()->id ?? '');

        // Validación: si no hay eventos creados para la fecha, no se puede actualizar/cerrar
        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';
        $eventsCount = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->count();
        if ($eventsCount === 0) {
            return redirect()->back()->with('error', 'No existen eventos creados para la fecha seleccionada. Debe crear eventos para continuar.');
        }

        $autoClose = $request->has('auto_close') && ((string) $request->input('auto_close') === '1');
        $cerrarDia = $request->has('cerrar_dia') && ((string) $request->input('cerrar_dia') === '1');

        DB::beginTransaction();
        try {
            $eventoIds = [];
            foreach ($personEvents as $pid => $evtIds) {
                foreach ((array) $evtIds as $eid) {
                    $eventoIds[] = (string) $eid;
                }
            }
            $eventoIds = array_values(array_unique(array_filter($eventoIds)));

            // Cargar targets para aplicar lote de depto correctamente
            $allEventsToday = PgEvento::query()
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->where('fecha_inicio', '<=', $dayEnd)
                ->where('fecha_fin', '>=', $dayStart)
                ->get();
            $targets = $this->loadEventTargets($allEventsToday->pluck('id')->all());

            $loteByEvento = [];
            if ($departamentoId) {
                foreach ($deptEventFiles as $eventoId => $files) {
                    $files = is_array($files) ? $files : [$files];
                    $files = array_values(array_filter($files));
                    if (count($files) === 0) {
                        continue;
                    }

                    // Obtener o crear lote
                    $lote = PgAsistenciaLote::query()
                        ->where('evento_id', $eventoId)
                        ->where('departamento_id', $departamentoId)
                        ->whereDate('fecha', $fecha)
                        ->where(function ($q) {
                            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                        })
                        ->first();

                    if (!$lote) {
                        $lote = PgAsistenciaLote::create([
                            'evento_id' => $eventoId,
                            'departamento_id' => $departamentoId,
                            'fecha' => $fecha,
                            'observacion' => null,
                        ]);
                    }

                    $existingCount = PgAsistenciaLoteArchivo::query()
                        ->where('asistencia_lote_id', $lote->id)
                        ->where(function ($q) {
                            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                        })
                        ->count();

                    if (($existingCount + count($files)) > 4) {
                        throw new \RuntimeException('El evento ' . $eventoId . ' ya tiene ' . $existingCount . ' evidencias. Solo se permiten 4 en total.');
                    }

                    foreach ($files as $file) {
                        $idArchivo = ArchivoDigitalService::store($file, 'Evidencia asistencia depto ' . $departamentoId . ' evento ' . $eventoId);
                        if (!$idArchivo) {
                            throw new \RuntimeException('No se pudo guardar una evidencia del evento ' . $eventoId);
                        }
                        PgAsistenciaLoteArchivo::create([
                            'asistencia_lote_id' => $lote->id,
                            'id_archivo' => $idArchivo,
                        ]);
                    }

                    $loteByEvento[$eventoId] = $lote->id;
                }
            }

            // Procesar asistencias por persona
            foreach ($personEvents as $personaId => $selected) {
                $personaId = (string) $personaId;
                $selected = array_values(array_unique(array_filter((array) $selected)));

                $filePersona = $request->file("person_file.$personaId");
                $idArchivoPersona = null;
                if (!$departamentoId && $filePersona) {
                    $idArchivoPersona = ArchivoDigitalService::store($filePersona, 'Evidencia asistencia persona ' . $personaId);
                }

                // Asistencias existentes del día para esta persona
                $existingRows = PgAsistenciaEvento::query()
                    ->where('persona_id', $personaId)
                    ->whereDate('fecha', $fecha)
                    ->where(function ($q) {
                        $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                    })
                    ->get();
                $existingByEvento = $existingRows->keyBy('evento_id');

                // Crear/actualizar seleccionadas
                foreach ($selected as $eventoId) {
                    $eventoId = (string) $eventoId;

                    $row = $existingByEvento->get($eventoId);
                    if (!$row) {
                        $row = new PgAsistenciaEvento();
                        $row->evento_id = $eventoId;
                        $row->persona_id = $personaId;
                        $row->fecha = $fecha;
                        if ($uid !== '') {
                            $row->creado_por = $uid;
                        }
                    }

                    if ($departamentoId) {
                        $row->asistencia_lote_id = $loteByEvento[$eventoId] ?? $row->asistencia_lote_id;
                        $row->id_archivo = null;
                    } else {
                        $row->asistencia_lote_id = null;
                        if ($idArchivoPersona) {
                            $row->id_archivo = $idArchivoPersona;
                        }
                    }

                    $row->estado_asistencia = 'A';
                    $row->estado = null;
                    if ($uid !== '') {
                        $row->actualizado_por = $uid;
                    }
                    $row->save();
                }

                // Eliminar lógicamente las no seleccionadas que existían
                foreach ($existingByEvento as $eventoId => $row) {
                    if (!in_array($eventoId, $selected, true)) {
                        // No seleccionada => Falta (F)
                        $row->estado_asistencia = 'F';
                        $row->estado = null;
                        if ($uid !== '') {
                            $row->actualizado_por = $uid;
                        }
                        $row->save();
                    }
                }
            }

            // Cerrar asistencia del día (registrar faltas) para la tabla actual
            if ($autoClose || $cerrarDia) {
                $personaIds = array_keys($personEvents);
                $this->rellenarFaltas($fecha, $departamentoId, $personaIds, $uid);
            }

            DB::commit();
            return redirect()->route('PgAsistenciasIndex', ['fecha' => $fecha, 'departamento_id' => $departamentoId])
                ->with('success', 'Asistencias actualizadas correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function loadEventTargets(array $eventIds): array
    {
        $eventIds = array_values(array_unique(array_filter($eventIds)));
        if (empty($eventIds)) {
            return ['deps' => [], 'pers' => []];
        }

        $deps = DB::table('pg_evento_departamento')
            ->whereIn('evento_id', $eventIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();

        $pers = DB::table('pg_evento_persona')
            ->whereIn('evento_id', $eventIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();

        $depMap = [];
        foreach ($deps as $r) {
            $depMap[$r->evento_id][] = (string) $r->departamento_id;
        }
        $perMap = [];
        foreach ($pers as $r) {
            $perMap[$r->evento_id][] = (string) $r->persona_id;
        }

        // normalizar unique
        foreach ($depMap as $eid => $arr) {
            $depMap[$eid] = array_values(array_unique($arr));
        }
        foreach ($perMap as $eid => $arr) {
            $perMap[$eid] = array_values(array_unique($arr));
        }

        return ['deps' => $depMap, 'pers' => $perMap];
    }

    private function eventAppliesToPerson(string $eventoId, string $personaId, ?string $departamentoId, array $targets): bool
    {
        $deps = $targets['deps'][$eventoId] ?? [];
        $pers = $targets['pers'][$eventoId] ?? [];

        // Global
        if (empty($deps) && empty($pers)) {
            return true;
        }

        // Unión: depto OR persona
        if (!empty($departamentoId) && in_array($departamentoId, $deps, true)) {
            return true;
        }
        if (in_array($personaId, $pers, true)) {
            return true;
        }

        return false;
    }

    private function eventAppliesToDepartment(string $eventoId, string $departamentoId, array $targets, array $personaDeptMap): bool
    {
        $deps = $targets['deps'][$eventoId] ?? [];
        $pers = $targets['pers'][$eventoId] ?? [];

        if (empty($deps) && empty($pers)) {
            return true;
        }
        if (in_array($departamentoId, $deps, true)) {
            return true;
        }

        // Evento sólo por personas: si alguna pertenece a este depto, lo mostramos
        if (!empty($pers)) {
            foreach ($pers as $pid) {
                if (($personaDeptMap[$pid] ?? null) === $departamentoId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Registra faltas (F) para los eventos aplicables del día.
     * Si no se pasan personaIds, lo calcula según filtro de departamento.
     */
    private function rellenarFaltas(string $fecha, ?string $departamentoId, ?array $personaIds = null, string $uid = ''): void
    {
        // Personas
        if ($personaIds === null) {
            $personasQ = PgPersona::query()->select(['id', 'departamento_id'])
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                });
            if ($departamentoId) {
                $personasQ->where('departamento_id', $departamentoId);
            }
            $personas = $personasQ->get();
        } else {
            $personaIds = array_values(array_unique(array_filter($personaIds)));
            if (empty($personaIds)) {
                return;
            }
            $personas = PgPersona::query()->select(['id', 'departamento_id'])
                ->whereIn('id', $personaIds)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->get();
        }

        $personIds = $personas->pluck('id')->all();
        if (empty($personIds)) {
            return;
        }

        // Eventos del día
        $dayStart = $fecha . ' 00:00:00';
        $dayEnd = $fecha . ' 23:59:59';
        $events = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('fecha_inicio', '<=', $dayEnd)
            ->where('fecha_fin', '>=', $dayStart)
            ->get();

        if ($events->isEmpty()) {
            return;
        }

        $eventIds = $events->pluck('id')->all();
        $targets = $this->loadEventTargets($eventIds);

        // Justificaciones aprobadas del día
        $justs = PgJustificacionAsistencia::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('persona_id', $personIds)
            ->where('estado_revision', 'A')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id']);
        $justMap = [];
        foreach ($justs as $j) {
            $justMap[$j->persona_id][(string) $j->evento_id] = true;
        }

        // Asistencias existentes del día (incluye borradas lógicas para reactivar)
        $existing = PgAsistenciaEvento::query()
            ->whereDate('fecha', $fecha)
            ->whereIn('persona_id', $personIds)
            ->whereIn('evento_id', $eventIds)
            ->get();

        $existingMap = [];
        foreach ($existing as $a) {
            $existingMap[$a->persona_id][(string) $a->evento_id] = $a;
        }

        foreach ($personas as $p) {
            $pid = (string) $p->id;
            $depId = $p->departamento_id ? (string) $p->departamento_id : null;

            foreach ($events as $e) {
                $eid = (string) $e->id;
                if (!$this->eventAppliesToPerson($eid, $pid, $depId, $targets)) {
                    continue;
                }

                $row = $existingMap[$pid][$eid] ?? null;

                // Si asistió, no tocar
                if ($row && (string) $row->estado_asistencia === 'A' && ($row->estado === null || $row->estado !== 'X')) {
                    continue;
                }

                // Si justificó, no registrar falta
                if (!empty($justMap[$pid][$eid])) {
                    continue;
                }

                if (!$row) {
                    $row = new PgAsistenciaEvento();
                    $row->evento_id = $eid;
                    $row->persona_id = $pid;
                    $row->fecha = $fecha;
                    if ($uid !== '') {
                        $row->creado_por = $uid;
                    }
                }

                $row->estado_asistencia = 'F';
                $row->estado = null;
                if ($uid !== '') {
                    $row->actualizado_por = $uid;
                }
                $row->save();
            }
        }
    }
}
