<?php

namespace App\Http\Controllers;

use App\Models\PgEvento;
use App\Models\PgAsistenciaEvento;
use App\Models\PgJustificacionAsistencia;
use App\Models\PgJustificacionAsistenciaArchivo;
use App\Models\PgDepartamento;
use App\Models\PgPersona;
use App\Services\ArchivoDigitalService;
use App\Services\PackedAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PgJustificacionesController extends Controller
{
    public function Index(Request $request)
    {
        $fecha = $request->input('fecha');
        $personaId = trim((string) $request->input('persona_id'));
        $eventoId = trim((string) $request->input('evento_id'));
        $personaQ = trim((string) $request->input('persona_q'));

        $q = PgJustificacionAsistencia::query()
            ->where(function ($qq) {
                $qq->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->with(['persona', 'evento']);

        if ($fecha) {
            $q->whereDate('fecha', $fecha);
        }
        if ($personaId !== '') {
            $q->where('persona_id', $personaId);
        }
        if ($personaQ !== '') {
            $q->whereHas('persona', function ($qq) use ($personaQ) {
                $like = '%' . str_replace('%', '\\%', $personaQ) . '%';
                $qq->where(function ($w) use ($like) {
                    $w->where('identificacion', 'like', $like)
                      ->orWhere('nombres', 'like', $like)
                      ->orWhere('apellido1', 'like', $like)
                      ->orWhere('apellido2', 'like', $like);
                });
            });
        }
        if ($eventoId !== '') {
            $q->where('evento_id', $eventoId);
        }

        $rows = $q->orderByDesc('fecha')->limit(200)->get();

        $personas = PgPersona::query()
            ->where(function ($qq) {
                $qq->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')
            ->get();

        $departamentos = PgDepartamento::query()
            ->where(function ($qq) {
                $qq->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('descripcion')
            ->get();

        $eventos = PgEvento::query()
            ->where(function ($qq) {
                $qq->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderByDesc('fecha_inicio')
            ->limit(200)
            ->get();

        return view('PgJustificaciones.index', [
            'fecha' => $fecha,
            'personaId' => $personaId !== '' ? $personaId : null,
            'eventoId' => $eventoId !== '' ? $eventoId : null,
            'personaQ' => $personaQ !== '' ? $personaQ : null,
            'rows' => $rows,
            'personas' => $personas,
            'departamentos' => $departamentos,
            'eventos' => $eventos,
        ]);
    }

    /**
     * Devuelve una justificación (JSON) para cargar el modal de edición.
     */
    public function Get(string $id)
    {
        $j = PgJustificacionAsistencia::query()
            ->where(function ($qq) {
                $qq->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->with(['persona.departamento.empresa', 'evento'])
            ->where('id', $id)
            ->firstOrFail();

        $empNombre = '';
        try {
            $empNombre = (string) ($j->persona && $j->persona->departamento && $j->persona->departamento->empresa
                ? $j->persona->departamento->empresa->nombre
                : '');
        } catch (\Throwable $e) {
            $empNombre = '';
        }

        $personaNombre = (string) ($j->persona ? $j->persona->nombre_completo : '');
        $personaText = trim($personaNombre);
        if ($empNombre !== '') {
            $personaText = trim($personaText . ' - ' . $empNombre);
        }

        return response()->json([
            'id' => $j->id,
            'persona_id' => $j->persona_id,
            'persona_text' => $personaText,
            'evento_id' => $j->evento_id,
            'evento_text' => (string) ($j->evento ? ($j->evento->titulo . ' (' . $j->evento->fecha_inicio . ' - ' . $j->evento->fecha_fin . ')') : ''),
            'fecha' => $j->fecha ? Carbon::parse($j->fecha)->format('Y-m-d') : null,
            'motivo' => $j->motivo,
            'estado_revision' => $j->estado_revision,
        ]);
    }

    /**
     * Actualiza una justificación desde modal.
     */
    public function Update(Request $request, string $id)
    {
        $j = PgJustificacionAsistencia::where('id', $id)->firstOrFail();
        $uid = (string) (Auth::user()->id ?? '');

        $request->validate([
            'persona_id' => 'required|string',
            'evento_id' => 'required|string',
            'fecha' => 'required|date',
            'motivo' => 'required|string|min:5',
            'archivo' => 'nullable|file|max:10240',
            'archivos.*' => 'nullable|file|max:10240',
        ]);

        // Validaciones de negocio
        try {
            $this->validateJustificacionOrFail(
                (string) $request->input('persona_id'),
                (string) $request->input('evento_id'),
                (string) $request->input('fecha'),
                $id
            );
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors(['general' => $e->getMessage()]);
        }

        DB::beginTransaction();
        try {
            // Si suben un archivo principal nuevo, lo reemplazamos
            if ($request->hasFile('archivo')) {
                $idArchivo = ArchivoDigitalService::store($request->file('archivo'), 'Justificación asistencia (actualizada)');
                $j->id_archivo = $idArchivo;
            }

            $j->persona_id = $request->input('persona_id');
            $j->evento_id = $request->input('evento_id');
            $j->fecha = $request->input('fecha');
            $j->motivo = $request->input('motivo');
            // si estaba rechazada/aprobada, volver a pendiente al editar
            $j->estado_revision = 'P';
            if ($uid !== '') {
                $j->actualizado_por = $uid;
            }
            $j->save();

            // Adjuntos adicionales (se agregan)
            $more = $request->file('archivos', []);
            if (!is_array($more)) {
                $more = [$more];
            }
            foreach ($more as $f) {
                if (!$f) continue;
                $ida = ArchivoDigitalService::store($f, 'Justificación asistencia (adjunto)');
                if (!$ida) continue;
                PgJustificacionAsistenciaArchivo::create([
                    'justificacion_id' => $j->id,
                    'id_archivo' => $ida,
                ]);
            }

            DB::commit();
            return redirect()->route('PgJustificacionesIndex')->with('success', 'Justificación actualizada.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function Store(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|string',
            'evento_id' => 'required|string',
            'fecha' => 'required|date',
            'motivo' => 'required|string|min:5',
            'archivo' => 'nullable|file|max:10240',
            'archivos.*' => 'nullable|file|max:10240',
        ]);

        // Validaciones de negocio
        try {
            $this->validateJustificacionOrFail(
                (string) $request->input('persona_id'),
                (string) $request->input('evento_id'),
                (string) $request->input('fecha'),
                null
            );
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors(['general' => $e->getMessage()]);
        }

        DB::beginTransaction();
        try {
            $uid = (string) (Auth::user()->id ?? '');
            $idArchivo = null;
            if ($request->hasFile('archivo')) {
                $idArchivo = ArchivoDigitalService::store($request->file('archivo'), 'Justificación asistencia');
            }

            $j = PgJustificacionAsistencia::create([
                'persona_id' => $request->input('persona_id'),
                'evento_id' => $request->input('evento_id'),
                'fecha' => $request->input('fecha'),
                'motivo' => $request->input('motivo'),
                'estado_revision' => 'P',
                'id_archivo' => $idArchivo,
                'creado_por' => $uid !== '' ? $uid : null,
                'actualizado_por' => $uid !== '' ? $uid : null,
            ]);

            $more = $request->file('archivos', []);
            if (!is_array($more)) {
                $more = [$more];
            }
            foreach ($more as $f) {
                if (!$f) continue;
                $ida = ArchivoDigitalService::store($f, 'Justificación asistencia (adjunto)');
                if (!$ida) continue;
                PgJustificacionAsistenciaArchivo::create([
                    'justificacion_id' => $j->id,
                    'id_archivo' => $ida,
                ]);
            }

            DB::commit();
            return redirect()->route('PgJustificacionesIndex')->with('success', 'Justificación registrada.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function Aprobar(string $id)
    {
        $j = PgJustificacionAsistencia::where('id', $id)->firstOrFail();
        $j->estado_revision = 'A';
        $j->revisado_por = (string) (Auth::user()->id ?? null);
        $j->revisado_en = Carbon::now();
        $j->actualizado_por = (string) (Auth::user()->id ?? null);
        $j->save();
        return redirect()->back()->with('success', 'Justificación aprobada.');
    }

    public function Rechazar(string $id)
    {
        $j = PgJustificacionAsistencia::where('id', $id)->firstOrFail();
        $j->estado_revision = 'R';
        $j->revisado_por = (string) (Auth::user()->id ?? null);
        $j->revisado_en = Carbon::now();
        $j->actualizado_por = (string) (Auth::user()->id ?? null);
        $j->save();
        return redirect()->back()->with('success', 'Justificación rechazada.');
    }

    /**
     * Options para Select2 (bambox): Departamentos.
     */
    public function OptionsDepartamentos(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 30;

        $db = PgDepartamento::query()
            ->where(function ($qq) {
                $qq->whereNull('estado')->orWhere('estado', '<>', 'X');
            });

        if ($q !== '') {
            $like = '%' . str_replace('%', '', $q) . '%';
            $db->where('descripcion', 'like', $like);
        }

        $total = (clone $db)->count();
        $rows = $db->orderBy('descripcion')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'descripcion']);

        $results = [];
        if ($page === 1) {
            $results[] = ['id' => '', 'text' => '-- Todos --'];
        }
        foreach ($rows as $r) {
            $results[] = ['id' => (string) $r->id, 'text' => (string) $r->descripcion];
        }

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * Options para Select2 (bambox): Personas (filtrado por departamento + búsqueda).
     */
   public function OptionsPersonas(Request $request)
{
    $q = trim((string) $request->input('q', ''));
    $page = max(1, (int) $request->input('page', 1));
    $perPage = 30;

    $db = DB::table('pg_persona as p')
        ->leftJoin('pg_empresa as e', 'e.id', '=', 'p.empresa_id')
        ->where(function ($w) {
            $w->whereNull('p.estado')->orWhere('p.estado', '<>', 'X');
        });

    if ($q !== '') {
        $like = '%' . str_replace('%', '', $q) . '%';
        $db->where(function ($w) use ($like) {
            $w->where('p.identificacion', 'like', $like)
              ->orWhereRaw("CONCAT_WS(' ', p.apellido1, p.apellido2, p.nombres) LIKE ?", [$like])
              ->orWhereRaw("CONCAT_WS(' ', p.nombres, p.apellido1, p.apellido2) LIKE ?", [$like])
              ->orWhere('e.nombre', 'like', $like);
        });
    }

    $total = (clone $db)->count();

    $rows = $db->orderBy('p.apellido1')->orderBy('p.apellido2')->orderBy('p.nombres')
        ->offset(($page - 1) * $perPage)
        ->limit($perPage)
        ->get([
            'p.id',
            'p.identificacion',
            'p.nombres',
            'p.apellido1',
            'p.apellido2',
            DB::raw('e.nombre as empresa_nombre'),
        ]);

    $results = [];
    if ($page === 1) $results[] = ['id' => '', 'text' => '-- Seleccione --'];

    foreach ($rows as $r) {
        $nombre = trim(($r->apellido1 ?? '') . ' ' . ($r->apellido2 ?? '') . ' ' . ($r->nombres ?? ''));
        $emp = trim((string) ($r->empresa_nombre ?? ''));
        $text = $emp !== '' ? ($nombre . ' - ' . $emp) : $nombre;

        $results[] = ['id' => (string) $r->id, 'text' => $text];
    }

    return response()->json([
        'results' => $results,
        'pagination' => ['more' => ($page * $perPage) < $total],
    ]);
}

    /**
     * Options para Select2 (bambox): Eventos.
     * Requerimiento:
     *  - Cuando se selecciona Persona, solo mostrar eventos NO ASISTIDOS (estado_asistencia = 'F').
     *  - Si además se selecciona Fecha, filtrar a eventos con falta en esa fecha.
     *  - Si se selecciona Departamento, se mantiene el filtro de aplicabilidad (globales + asignados al depto).
     */
    public function OptionsEventos(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $depto = trim((string) $request->input('departamento_id'));
        $personaId = trim((string) $request->input('persona_id'));
        $fechaRaw = trim((string) $request->input('fecha'));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 30;

        // Soporte de búsqueda por fecha escrita en el buscador (dd/mm/yyyy o yyyy-mm-dd)
        $fechaFiltro = $this->parseFlexibleDate($fechaRaw);
        if (!$fechaFiltro && $q !== '') {
            $fechaFiltro = $this->parseFlexibleDate($q);
        }

        // Si no hay persona seleccionada, no devolvemos eventos (evita listar TODOS).
        // En edición, el select2 ya trae la opción seleccionada (por option temporal),
        // y al abrir/buscar se aplicará este filtro.
        if ($personaId === '') {
            return response()->json([
                'results' => [ ['id' => '', 'text' => '-- Seleccione --'] ],
                'pagination' => ['more' => false],
            ]);
        }

        // Soporta formato compacto (evento_id/estado_asistencia en listas JSON/CSV):
        // obtenemos primero IDs de eventos donde la persona tiene estado_asistencia='F'.
        $asistRows = DB::table('pg_asistencia_evento as pae')
            ->where('pae.persona_id', $personaId)
            ->where(function ($qq) {
                $qq->whereNull('pae.estado')->orWhere('pae.estado', '<>', 'X');
            });
        if ($fechaFiltro) {
            $asistRows->whereDate('pae.fecha', $fechaFiltro);
        }
        $asistRows = $asistRows->get(['pae.evento_id', 'pae.estado_asistencia', 'pae.fecha']);

        $eventoIdsConFalta = [];
        $faltaFechaPorEvento = [];
        foreach ($asistRows as $row) {
            $eventos = PackedAttendanceService::decodeList((string) ($row->evento_id ?? ''));
            $estados = PackedAttendanceService::decodeList((string) ($row->estado_asistencia ?? ''));
            $len = min(count($eventos), count($estados));
            $fechaRow = $row->fecha ? Carbon::parse($row->fecha)->format('Y-m-d') : null;

            for ($i = 0; $i < $len; $i++) {
                $eid = trim((string) ($eventos[$i] ?? ''));
                $estadoAsistencia = strtoupper(trim((string) ($estados[$i] ?? '')));
                if ($eid === '' || $estadoAsistencia !== 'F') {
                    continue;
                }
                $eventoIdsConFalta[$eid] = true;
                if ($fechaRow && !isset($faltaFechaPorEvento[$eid])) {
                    $faltaFechaPorEvento[$eid] = $fechaRow;
                }
            }
        }

        // Si no hay faltas, no hay eventos para justificar.
        if (empty($eventoIdsConFalta)) {
            return response()->json([
                'results' => [ ['id' => '', 'text' => '-- Seleccione --'] ],
                'pagination' => ['more' => false],
            ]);
        }

        // Tabla real del modelo: pg_eventos (no pg_evento)
        $db = PgEvento::query()
            ->from('pg_eventos')
            ->where(function ($qq) {
                $qq->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->whereIn('pg_eventos.id', array_keys($eventoIdsConFalta));

        if ($depto !== '') {
            $db->where(function ($w) use ($depto) {
                $w->whereExists(function ($sq) use ($depto) {
                    $sq->select(DB::raw(1))
                        ->from('pg_evento_departamento as ped')
                        ->whereColumn('ped.evento_id', 'pg_eventos.id')
                        ->where('ped.departamento_id', $depto)
                        ->where(function ($qq) {
                            $qq->whereNull('ped.estado')->orWhere('ped.estado', '<>', 'X');
                        });
                })->orWhere(function ($g) {
                    $g->whereNotExists(function ($sq) {
                        $sq->select(DB::raw(1))
                            ->from('pg_evento_departamento as ped2')
                            ->whereColumn('ped2.evento_id', 'pg_eventos.id')
                            ->where(function ($qq) {
                                $qq->whereNull('ped2.estado')->orWhere('ped2.estado', '<>', 'X');
                            });
                    })->whereNotExists(function ($sq) {
                        $sq->select(DB::raw(1))
                            ->from('pg_evento_persona as pep2')
                            ->whereColumn('pep2.evento_id', 'pg_eventos.id')
                            ->where(function ($qq) {
                                $qq->whereNull('pep2.estado')->orWhere('pep2.estado', '<>', 'X');
                            });
                    });
                });
            });
        }

        if ($q !== '') {
            // Si el usuario está escribiendo una fecha, no filtramos por título (para que funcione la búsqueda por fecha)
            if (!$fechaFiltro) {
                $like = '%' . str_replace('%', '', $q) . '%';
                $db->where('titulo', 'like', $like);
            }
        }

        // Filtrar eventos que contengan esa fecha (inclusive)
        if ($fechaFiltro) {
            $db->whereDate('fecha_inicio', '<=', $fechaFiltro)
               ->whereDate('fecha_fin', '>=', $fechaFiltro);
        }

        $total = (clone $db)->count();
        $rows = $db->orderByDesc('fecha_inicio')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['pg_eventos.id', 'pg_eventos.titulo', 'pg_eventos.fecha_inicio', 'pg_eventos.fecha_fin']);

        $results = [];
        if ($page === 1) {
            $results[] = ['id' => '', 'text' => '-- Seleccione --'];
        }
        foreach ($rows as $e) {
            $fi = (string) $e->fecha_inicio;
            $ff = (string) $e->fecha_fin;
            $faltaFecha = (string) ($faltaFechaPorEvento[(string) $e->id] ?? '');
            // Devolvemos inicio/fin para autocompletar la fecha al seleccionar el evento
            $results[] = [
                'id' => (string) $e->id,
                'text' => (string) $e->titulo . " ($fi - $ff)",
                'inicio' => $fi,
                'fin' => $ff,
                'falta_fecha' => $faltaFecha,
            ];
        }

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * Parsea fecha flexible: acepta yyyy-mm-dd, dd/mm/yyyy y dd-mm-yyyy.
     * Retorna yyyy-mm-dd o null.
     */
    private function parseFlexibleDate(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') return null;

        // yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }

        // d/m/yyyy, dd/mm/yyyy, d-m-yyyy o dd-mm-yyyy
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $v, $m)) {
            $d = $m[1];
            $mo = $m[2];
            $y = $m[3];
            return sprintf('%04d-%02d-%02d', (int)$y, (int)$mo, (int)$d);
        }

        return null;
    }

    /**
     * Endpoint para validar en vivo (AJAX) antes de guardar.
     */
    public function Validar(Request $request)
    {
        $personaId = trim((string) $request->input('persona_id'));
        $eventoId = trim((string) $request->input('evento_id'));
        $fecha = trim((string) $request->input('fecha'));
        $ignoreId = trim((string) $request->input('ignore_id'));
        if ($ignoreId === '') {
            $ignoreId = null;
        }

        try {
            if ($personaId === '' || $eventoId === '' || $fecha === '') {
                return response()->json(['ok' => false, 'message' => 'Seleccione Persona, Evento y Fecha.']);
            }
            $this->validateJustificacionOrFail($personaId, $eventoId, $fecha, $ignoreId);
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    private function validateJustificacionOrFail(string $personaId, string $eventoId, string $fecha, ?string $ignoreJustId): void
    {
        $persona = PgPersona::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('id', $personaId)
            ->first();
        if (!$persona) {
            throw new \RuntimeException('La persona seleccionada no es válida.');
        }

        $evento = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->where('id', $eventoId)
            ->first();
        if (!$evento) {
            throw new \RuntimeException('El evento seleccionado no es válido.');
        }

        $f = Carbon::parse($fecha)->format('Y-m-d');
        $fi = Carbon::parse($evento->fecha_inicio)->format('Y-m-d');
        $ff = Carbon::parse($evento->fecha_fin)->format('Y-m-d');
        if ($f < $fi || $f > $ff) {
            throw new \RuntimeException('La fecha seleccionada no corresponde al rango del evento.');
        }

        // Aplica a persona: global / por depto / por persona
        $deptoId = (string) ($persona->departamento_id ?? '');
        $deps = DB::table('pg_evento_departamento')
            ->where('evento_id', $eventoId)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->pluck('departamento_id')
            ->map(fn($v) => (string) $v)
            ->all();

        $pers = DB::table('pg_evento_persona')
            ->where('evento_id', $eventoId)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->pluck('persona_id')
            ->map(fn($v) => (string) $v)
            ->all();

        $isGlobal = empty($deps) && empty($pers);
        $aplica = $isGlobal
            || (!empty($deptoId) && in_array($deptoId, $deps, true))
            || in_array($personaId, $pers, true);

        if (!$aplica) {
            throw new \RuntimeException('El evento seleccionado no aplica a la persona/departamento.');
        }

        $asistio = PgAsistenciaEvento::query()
            ->where('persona_id', $personaId)
            ->where('evento_id', $eventoId)
            ->whereDate('fecha', $f)
            ->whereIn('estado_asistencia', ['A', 'T'])
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->exists();

        if ($asistio) {
            throw new \RuntimeException('No se puede justificar: la persona ya registra asistencia para ese evento y fecha.');
        }

        $jQ = PgJustificacionAsistencia::query()
            ->where('persona_id', $personaId)
            ->where('evento_id', $eventoId)
            ->whereDate('fecha', $f)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            });

        if ($ignoreJustId) {
            $jQ->where('id', '<>', $ignoreJustId);
        }

        if ($jQ->exists()) {
            throw new \RuntimeException('Ya existe una justificación registrada para esa persona, evento y fecha.');
        }
    }
}
