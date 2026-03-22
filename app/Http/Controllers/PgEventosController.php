<?php

namespace App\Http\Controllers;

use App\Models\PgDepartamento;
use App\Models\PgEvento;
use App\Models\PgPersona;
use App\Models\PgConfiguracion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PgEventosController extends Controller
{
    public function Index()
    {
        $departamentos = PgDepartamento::orderBy('descripcion')->get();
        // En pg_persona el esquema usa apellido1/apellido2; no existe "apellidos".
        $personas = PgPersona::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('apellido1')
            ->orderBy('apellido2')
            ->orderBy('nombres')
            ->get();

        // Próximos eventos: mostrar desde HOY en adelante (incluye eventos de hoy aunque ya hayan finalizado)
        // Esto evita que el panel quede vacío cuando el día ya avanzó.
        $upcoming = PgEvento::query()
            ->whereDate('fecha_inicio', '>=', Carbon::today())
            ->orderBy('fecha_inicio')
            ->limit(10)
            ->get();

        return view('PgEventos.index', compact('departamentos', 'personas', 'upcoming'));
    }

    public function Eliminados()
    {
        $eventos = PgEvento::soloEliminados()->orderByDesc('updated_at')->paginate(20);
        return view('PgEventos.eliminados', compact('eventos'));
    }

    public function Feed()
    {
        $events = PgEvento::orderBy('fecha_inicio')->get();
        $out = [];
        foreach ($events as $e) {
            $end = $e->fecha_fin ? Carbon::parse($e->fecha_fin)->addDay()->format('Y-m-d') : null;
            $out[] = [
                'id' => $e->id,
                'title' => $e->titulo,
                'start' => Carbon::parse($e->fecha_inicio)->format('Y-m-d'),
                'end' => $end,
                'allDay' => true,
                'backgroundColor' => $e->color ?: null,
                'borderColor' => $e->color ?: null,
            ];
        }

        return response()->json($out);
    }


    public function Upcoming()
    {
        // Mismo criterio que en Index(): desde HOY en adelante.
        $upcoming = PgEvento::query()
            ->whereDate('fecha_inicio', '>=', Carbon::today())
            ->orderBy('fecha_inicio')
            ->limit(10)
            ->get();

        $out = [];
        foreach ($upcoming as $e) {
            $out[] = [
                'id' => $e->id,
                'titulo' => $e->titulo,
                // ISO para inputs y calendar
                'fecha_inicio' => Carbon::parse($e->fecha_inicio)->format('Y-m-d\TH:i:s'),
                'fecha_fin' => Carbon::parse($e->fecha_fin)->format('Y-m-d\TH:i:s'),
                // display según configuración
                'fecha_inicio_display' => ($e->fecha_inicio ? Carbon::parse($e->fecha_inicio)->format('d/m/Y H:i:s') : null),
                'fecha_fin_display' => ($e->fecha_fin ? Carbon::parse($e->fecha_fin)->format('d/m/Y H:i:s') : null),
                'color' => $e->color,
            ];
        }

        return response()->json($out);
    }

            public function Get($id)
    {
        $evento = PgEvento::conEliminados()->where('id', $id)->firstOrFail();

        $departamentos = [];
        $personas = [];

        if (!empty($evento->departamento_id)) {
            $departamentos = json_decode($evento->departamento_id, true) ?: [];
        }
        if (!empty($evento->persona_id)) {
            $personas = json_decode($evento->persona_id, true) ?: [];
        }

        return response()->json([
            'id' => $evento->id,
            'titulo' => $evento->titulo,
            // ISO para inputs y calendar
            'fecha_inicio' => Carbon::parse($evento->fecha_inicio)->format('Y-m-d\TH:i:s'),
            'fecha_fin' => Carbon::parse($evento->fecha_fin)->format('Y-m-d\TH:i:s'),
            // display según configuración
            'fecha_inicio_display' => ($evento->fecha_inicio ? Carbon::parse($evento->fecha_inicio)->format('d/m/Y H:i:s') : null),
            'fecha_fin_display' => ($evento->fecha_fin ? Carbon::parse($evento->fecha_fin)->format('d/m/Y H:i:s') : null),
            'color' => $evento->color,
            'descripcion' => $evento->descripcion,
            'todos_departamentos' => empty($departamentos),
            'todas_personas' => empty($personas),
            'departamentos' => $departamentos,
            'personas' => $personas,
        ]);
    }

    public function Store(Request $request)
    {
        $v = $this->validator($request);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        $depIds = $this->normalizeIds($request->input('departamentos', []));
        $perIds = $this->normalizeIds($request->input('personas', []));

        $todosDepartamentos = (bool) $request->boolean('todos_departamentos');
        $todasPersonas = (bool) $request->boolean('todas_personas');

        if ($todosDepartamentos) {
            $depIds = [];
        }
        if ($todasPersonas) {
            $perIds = [];
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
                // Compatibilidad: guardamos arrays en JSON (si está vacío, significa "todos")
                'departamento_id' => empty($depIds) ? null : json_encode(array_values($depIds)),
                'persona_id' => empty($perIds) ? null : json_encode(array_values($perIds)),
            ]);

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

        $depIds = $this->normalizeIds($request->input('departamentos', []));
        $perIds = $this->normalizeIds($request->input('personas', []));

        $todosDepartamentos = (bool) $request->boolean('todos_departamentos');
        $todasPersonas = (bool) $request->boolean('todas_personas');

        if ($todosDepartamentos) {
            $depIds = [];
        }
        if ($todasPersonas) {
            $perIds = [];
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

        // También marcamos pivots como eliminados
        DB::table('pg_evento_departamento')->where('evento_id', $evento->id)->update(['estado' => 'X', 'updated_at' => now()]);
        DB::table('pg_evento_persona')->where('evento_id', $evento->id)->update(['estado' => 'X', 'updated_at' => now()]);

        return response()->json(['ok' => true, 'message' => 'Evento eliminado.']);
    }

    public function Restore($id)
    {
        $evento = PgEvento::soloEliminados()->where('id', $id)->firstOrFail();
        $evento->estado = null;
        $evento->save();
        return redirect()->back()->with('success', 'Evento restaurado correctamente.');
    }

    private function validator(Request $request)
    {
        $fmt = PgConfiguracion::formatoFecha();
        return Validator::make($request->all(), [
            'titulo' => 'required|string|max:191',
            // Acepta el formato configurado (con hora) y también formatos comunes.
            'fecha_inicio' => [
                'required',
                function ($attribute, $value, $fail) use ($fmt) {
                    if (!PgConfiguracion::parseFecha($value)) {
                        $fail("El campo fecha inicio no tiene un formato válido. Formato esperado: {$fmt}");
                    }
                },
            ],
            'fecha_fin' => [
                'required',
                function ($attribute, $value, $fail) use ($fmt, $request) {
                    $fi = PgConfiguracion::parseFecha($request->input('fecha_inicio'));
                    $ff = PgConfiguracion::parseFecha($value);
                    if (!$ff) {
                        $fail("El campo fecha fin no tiene un formato válido. Formato esperado: {$fmt}");
                        return;
                    }
                    if ($fi && $ff->lt($fi)) {
                        $fail('La fecha fin debe ser mayor o igual a la fecha inicio.');
                    }
                },
            ],
            'color' => 'nullable|string|max:191',
            'descripcion' => 'nullable|string',
            'todos_departamentos' => 'nullable|boolean',
            'todas_personas' => 'nullable|boolean',
            'departamentos' => 'nullable|array',
            'personas' => 'nullable|array',
        ]);
    }

    private function normalizeIds($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $v) {
            $v = is_string($v) ? trim($v) : (string) $v;
            if ($v !== '') {
                $out[] = $v;
            }
        }
        return array_values(array_unique($out));
    }

    private function syncPivots(string $eventoId, array $depIds, array $perIds): void
    {
        $now = now();

        if (!empty($depIds)) {
            $rows = [];
            foreach ($depIds as $depId) {
                $rows[] = [
                    'evento_id' => $eventoId,
                    'departamento_id' => $depId,
                    'estado' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('pg_evento_departamento')->insert($rows);
        }

        if (!empty($perIds)) {
            $rows = [];
            foreach ($perIds as $perId) {
                $rows[] = [
                    'evento_id' => $eventoId,
                    'persona_id' => $perId,
                    'estado' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('pg_evento_persona')->insert($rows);
        }
    }
}
