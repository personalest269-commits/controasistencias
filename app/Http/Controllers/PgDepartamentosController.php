<?php

namespace App\Http\Controllers;

use App\Models\PgDepartamento;
use App\Models\PgPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PgDepartamentosController extends Controller
{
    public function Index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $departamentos = PgDepartamento::query()
            ->with('empresa')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('id', 'like', "%{$q}%")
                      ->orWhere('codigo', 'like', "%{$q}%")
                      ->orWhere('descripcion', 'like', "%{$q}%");
                });
            })
            ->orderBy('descripcion')
            ->paginate(20)
            ->appends(['q' => $q]);
        // En pg_persona el esquema usa apellido1/apellido2; no existe una columna "apellidos".
        $jefes = PgPersona::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('apellido1')
            ->orderBy('apellido2')
            ->orderBy('nombres')
            ->get();

        return view('PgDepartamentos.index', compact('departamentos', 'jefes', 'q'));
    }

    public function Eliminados()
    {
        $departamentos = PgDepartamento::soloEliminados()->orderBy('descripcion')->paginate(20);
        return view('PgDepartamentos.eliminados', compact('departamentos'));
    }

    public function Store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'empresa_id' => 'nullable|string|max:10|exists:pg_empresa,id',
            'codigo' => 'nullable|string|max:10',
            'descripcion' => 'required|string|max:255',
            'cod_padre' => 'nullable|string|max:10',
            'cod_programa' => 'nullable|string|max:10',
            'ultimo_nivel' => 'nullable|in:S,N',
            'vigencia_desde' => 'nullable|date',
            'vigencia_hasta' => 'nullable|date',
            'id_jefe' => 'nullable|string|max:10',
            'identificador_activo_fijo' => 'nullable|string|max:2',
            'extension_telefonica' => 'nullable|string|max:5',
            'cod_clasificacion_departamento' => 'nullable|string|max:3',
        ]);

        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        }

        $dep = PgDepartamento::create([
            'empresa_id' => $request->input('empresa_id'),
            'codigo' => $request->input('codigo'),
            'descripcion' => $request->input('descripcion'),
            'cod_padre' => $request->input('cod_padre'),
            'cod_programa' => $request->input('cod_programa'),
            'ultimo_nivel' => $request->input('ultimo_nivel', 'N'),
            'vigencia_desde' => $request->input('vigencia_desde'),
            'vigencia_hasta' => $request->input('vigencia_hasta'),
            'id_jefe' => $request->input('id_jefe'),
            'identificador_activo_fijo' => $request->input('identificador_activo_fijo'),
            'extension_telefonica' => $request->input('extension_telefonica'),
            'cod_clasificacion_departamento' => $request->input('cod_clasificacion_departamento'),
        ]);

        // Dejar "seleccionado" el nuevo registro (resaltado y filtrado en la vista)
        return redirect()
            ->route('PgDepartamentosIndex', ['q' => $dep->id])
            ->with('success', 'Departamento creado correctamente.')
            ->with('created_id', $dep->id);
    }

    public function Edit($id)
    {
        $departamento = PgDepartamento::conEliminados()->with('empresa')->where('id', $id)->firstOrFail();
        $jefes = PgPersona::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('apellido1')
            ->orderBy('apellido2')
            ->orderBy('nombres')
            ->get();

        return view('PgDepartamentos.edit', compact('departamento', 'jefes'));
    }

    public function Update(Request $request, $id)
    {
        $departamento = PgDepartamento::conEliminados()->where('id', $id)->firstOrFail();

        $v = Validator::make($request->all(), [
            'empresa_id' => 'nullable|string|max:10|exists:pg_empresa,id',
            'codigo' => 'nullable|string|max:10',
            'descripcion' => 'required|string|max:255',
            'cod_padre' => 'nullable|string|max:10',
            'cod_programa' => 'nullable|string|max:10',
            'ultimo_nivel' => 'nullable|in:S,N',
            'vigencia_desde' => 'nullable|date',
            'vigencia_hasta' => 'nullable|date',
            'id_jefe' => 'nullable|string|max:10',
            'identificador_activo_fijo' => 'nullable|string|max:2',
            'extension_telefonica' => 'nullable|string|max:5',
            'cod_clasificacion_departamento' => 'nullable|string|max:3',
        ]);

        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        }

        $departamento->fill($request->only([
            'empresa_id',
            'codigo',
            'descripcion',
            'cod_padre',
            'cod_programa',
            'ultimo_nivel',
            'vigencia_desde',
            'vigencia_hasta',
            'id_jefe',
            'identificador_activo_fijo',
            'extension_telefonica',
            'cod_clasificacion_departamento',
        ]));

        $departamento->save();

        return redirect()->route('PgDepartamentosIndex')->with('success', 'Departamento actualizado correctamente.');
    }

    public function Delete($id)
    {
        $departamento = PgDepartamento::where('id', $id)->firstOrFail();
        $departamento->delete();
        return redirect()->back()->with('success', 'Departamento eliminado correctamente.');
    }

    public function Restore($id)
    {
        $departamento = PgDepartamento::soloEliminados()->where('id', $id)->firstOrFail();
        $departamento->estado = null;
        $departamento->save();
        return redirect()->back()->with('success', 'Departamento restaurado correctamente.');
    }
}
