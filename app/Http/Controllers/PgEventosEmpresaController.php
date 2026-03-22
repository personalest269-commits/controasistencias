<?php

namespace App\Http\Controllers;

use App\Models\PgDepartamento;
use App\Models\PgEmpresa;
use App\Models\PgEvento;
use App\Models\PgPersona;
use App\Models\PgConfiguracion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Calendario de eventos por EMPRESA.
 *
 * Importante:
 * - La tabla pg_evento sigue guardando departamento_id/persona_id como JSON (si está vacío => "todos").
 * - En este formulario, el usuario selecciona empresas y el backend expande a los departamentos de esas empresas.
 */
class PgEventosEmpresaController extends Controller
{
    public function Index()
    {
        $empresas = PgEmpresa::orderBy('nombre')->get();

        $personas = PgPersona::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('apellido1')
            ->orderBy('apellido2')
            ->orderBy('nombres')
            ->get();

        $upcoming = PgEvento::query()
            ->whereDate('fecha_inicio', '>=', Carbon::today())
            ->orderBy('fecha_inicio')
            ->limit(10)
            ->get();

        return view('PgEventosEmpresa.index', compact('empresas', 'personas', 'upcoming'));
    }

    public function Get($id)
    {
        $evento = PgEvento::conEliminados()->where('id', $id)->firstOrFail();

        $depIds = [];
        $perIds = [];

        if (!empty($evento->departamento_id)) {
            $depIds = json_decode($evento->departamento_id, true) ?: [];
        }
        if (!empty($evento->persona_id)) {
            $perIds = json_decode($evento->persona_id, true) ?: [];
        }

        // Derivar empresas desde los departamentos guardados.
        $empresaIds = [];
        if (!empty($depIds)) {
            $empresaIds = PgDepartamento::query()
                ->whereIn('id', $depIds)
                ->pluck('empresa_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        return response()->json([
            'id' => $evento->id,
            'titulo' => $evento->titulo,
            'fecha_inicio' => Carbon::parse($evento->fecha_inicio)->format('Y-m-d\TH:i:s'),
            'fecha_fin' => Carbon::parse($evento->fecha_fin)->format('Y-m-d\TH:i:s'),
            'fecha_inicio_display' => ($evento->fecha_inicio ? Carbon::parse($evento->fecha_inicio)->format('d/m/Y H:i:s') : null),
            'fecha_fin_display' => ($evento->fecha_fin ? Carbon::parse($evento->fecha_fin)->format('d/m/Y H:i:s') : null),
            'color' => $evento->color,
            'descripcion' => $evento->descripcion,

            'todos_empresas' => empty($depIds),
            'todas_personas' => empty($perIds),
            'empresas' => $empresaIds,
            'personas' => $perIds,
        ]);
    }

    public function Store(Request $request)
    {
        $v = $this->validator($request);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        if ($limite = $this->validateDailyEventLimit($request)) {
            return $limite;
        }

        $empresaIds = $this->normalizeIds($request->input('empresas', []));
        $perIds = $this->normalizeIds($request->input('personas', []));

        $todosEmpresas = (bool) $request->boolean('todos_empresas');
        $todasPersonas = (bool) $request->boolean('todas_personas');

        if ($todosEmpresas) {
            $empresaIds = [];
        }
        if ($todasPersonas) {
            $perIds = [];
        }

        // Expandir empresas -> departamentos
        $depIds = [];
        if (!$todosEmpresas && !empty($empresaIds)) {
            $depIds = PgDepartamento::query()
                ->whereIn('empresa_id', $empresaIds)
                ->pluck('id')
                ->values()
                ->toArray();
        }

        DB::beginTransaction();
        try {
            $fi = PgConfiguracion::parseFecha($request->input('fecha_inicio'));
            $ff = PgConfiguracion::parseFecha($request->input('fecha_fin'));

            $evento = PgEvento::create([
                'titulo' => $request->input('titulo'),
                'fecha_inicio' => $fi ? $fi->format('Y-m-d H:i:s') : PgConfiguracion::normalizeDatetimeLocal($request->input('fecha_inicio')),
                'fecha_fin' => $ff ? $ff->format('Y-m-d H:i:s') : PgConfiguracion::normalizeDatetimeLocal($request->input('fecha_fin')),
                'color' => $request->input('color'),
                'descripcion' => $request->input('descripcion'),
                // guardamos departamentos expandidos (si está vacío => todos)
                'departamento_id' => empty($depIds) ? null : json_encode(array_values($depIds)),
                'persona_id' => empty($perIds) ? null : json_encode(array_values($perIds)),
            ]);

            // Pivots
            $this->syncPivots($evento->id, $depIds, $perIds);

            DB::commit();
            return response()->json(['ok' => true, 'message' => 'Evento creado correctamente.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'message' => 'No se pudo crear el evento.', 'error' => $e->getMessage()], 500);
        }
    }

    public function Update(Request $request, $id)
    {
        $evento = PgEvento::where('id', $id)->firstOrFail();

        $v = $this->validator($request);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        if ($limite = $this->validateDailyEventLimit($request, $evento->id)) {
            return $limite;
        }

        $empresaIds = $this->normalizeIds($request->input('empresas', []));
        $perIds = $this->normalizeIds($request->input('personas', []));

        $todosEmpresas = (bool) $request->boolean('todos_empresas');
        $todasPersonas = (bool) $request->boolean('todas_personas');

        if ($todosEmpresas) {
            $empresaIds = [];
        }
        if ($todasPersonas) {
            $perIds = [];
        }

        $depIds = [];
        if (!$todosEmpresas && !empty($empresaIds)) {
            $depIds = PgDepartamento::query()
                ->whereIn('empresa_id', $empresaIds)
                ->pluck('id')
                ->values()
                ->toArray();
        }

        DB::beginTransaction();
        try {
            $fi = PgConfiguracion::parseFecha($request->input('fecha_inicio'));
            $ff = PgConfiguracion::parseFecha($request->input('fecha_fin'));

            $evento->fill([
                'titulo' => $request->input('titulo'),
                'fecha_inicio' => $fi ? $fi->format('Y-m-d H:i:s') : PgConfiguracion::normalizeDatetimeLocal($request->input('fecha_inicio')),
                'fecha_fin' => $ff ? $ff->format('Y-m-d H:i:s') : PgConfiguracion::normalizeDatetimeLocal($request->input('fecha_fin')),
                'color' => $request->input('color'),
                'descripcion' => $request->input('descripcion'),
                'departamento_id' => empty($depIds) ? null : json_encode(array_values($depIds)),
                'persona_id' => empty($perIds) ? null : json_encode(array_values($perIds)),
            ]);
            $evento->save();

            // Marcamos pivots actuales como eliminados lógicos
            DB::table('pg_evento_departamento')->where('evento_id', $evento->id)->update(['estado' => 'X', 'updated_at' => now()]);
            DB::table('pg_evento_persona')->where('evento_id', $evento->id)->update(['estado' => 'X', 'updated_at' => now()]);

            $this->syncPivots($evento->id, $depIds, $perIds);

            DB::commit();
            return response()->json(['ok' => true, 'message' => 'Evento actualizado correctamente.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'message' => 'No se pudo actualizar el evento.', 'error' => $e->getMessage()], 500);
        }
    }

    public function Delete($id)
    {
        $evento = PgEvento::where('id', $id)->firstOrFail();
        $evento->delete();
        return response()->json(['ok' => true, 'message' => 'Evento eliminado correctamente.']);
    }

    // -------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------
    private function validator(Request $request)
    {
        return Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required'],
            'fecha_fin' => ['required'],
            // empresas/personas llegan como array (o vacías si "todos")
            'empresas' => ['array'],
            'personas' => ['array'],
            'todos_empresas' => ['nullable'],
            'todas_personas' => ['nullable'],
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'fecha_inicio.required' => 'La fecha inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha fin es obligatoria.',
        ]);
    }

    private function validateDailyEventLimit(Request $request, ?string $excludeEventId = null)
    {
        $fechaInicio = PgConfiguracion::parseFecha($request->input('fecha_inicio'));

        if (!$fechaInicio) {
            $fechaInicioNormalizada = PgConfiguracion::normalizeDatetimeLocal($request->input('fecha_inicio'));
            $fechaInicio = $fechaInicioNormalizada ? Carbon::parse($fechaInicioNormalizada) : null;
        }

        if (!$fechaInicio) {
            return null;
        }

        $query = PgEvento::query()->whereDate('fecha_inicio', $fechaInicio->toDateString());

        if ($excludeEventId) {
            $query->where('id', '<>', $excludeEventId);
        }

        $cantidadEventos = $query->count();

        if ($cantidadEventos >= 5) {
            return response()->json([
                'ok' => false,
                'errors' => [
                    'fecha_inicio' => ['Solo se pueden crear 5 eventos por día.'],
                ],
            ], 422);
        }

        return null;
    }

    private function normalizeIds($ids)
    {
        if (!is_array($ids)) return [];
        return collect($ids)
            ->filter(function ($v) {
                return !is_null($v) && $v !== '' && $v !== '__ALL__';
            })
            ->values()
            ->toArray();
    }

    private function syncPivots($eventoId, array $depIds, array $perIds)
    {
        // Si está vacío => "todos" (no insertamos pivots)
        if (!empty($depIds)) {
            foreach ($depIds as $d) {
                DB::table('pg_evento_departamento')->insert([
                    'evento_id' => $eventoId,
                    'departamento_id' => $d,
                    'estado' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (!empty($perIds)) {
            foreach ($perIds as $p) {
                DB::table('pg_evento_persona')->insert([
                    'evento_id' => $eventoId,
                    'persona_id' => $p,
                    'estado' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
