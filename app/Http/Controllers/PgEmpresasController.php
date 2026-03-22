<?php

namespace App\Http\Controllers;

use App\Models\PgEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PgEmpresasController extends Controller
{
    public function Index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $empresas = PgEmpresa::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('id', 'like', "%{$q}%")
                        ->orWhere('nombre', 'like', "%{$q}%")
                        ->orWhere('ruc', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(20)
            ->appends(['q' => $q]);

        return view('PgEmpresas.index', compact('empresas', 'q'));
    }

    public function Eliminados()
    {
        $empresas = PgEmpresa::soloEliminados()->orderBy('nombre')->paginate(20);
        return view('PgEmpresas.eliminados', compact('empresas'));
    }

    public function Store(Request $request)
    {
        $v = Validator::make($request->all(), [
            // id lo genera trigger (no se valida aquí)
            'nombre' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|string|max:100',
        ]);

        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        }

        $empresa = PgEmpresa::create([
            // IMPORTANT: dejamos id NULL para que el trigger lo asigne
            'nombre' => $request->input('nombre'),
            'ruc' => $request->input('ruc'),
            'direccion' => $request->input('direccion'),
            'telefono' => $request->input('telefono'),
            'correo' => $request->input('correo'),
        ]);

        return redirect()
            ->route('PgEmpresasIndex', ['q' => $empresa->id])
            ->with('success', 'Empresa creada correctamente.')
            ->with('created_id', $empresa->id);
    }

    public function Edit($id)
    {
        $empresa = PgEmpresa::conEliminados()->where('id', $id)->firstOrFail();
        return view('PgEmpresas.edit', compact('empresa'));
    }

    public function Update(Request $request, $id)
    {
        $empresa = PgEmpresa::conEliminados()->where('id', $id)->firstOrFail();

        $v = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|string|max:100',
        ]);

        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        }

        $empresa->fill($request->only(['nombre', 'ruc', 'direccion', 'telefono', 'correo']));
        $empresa->save();

        return redirect()->route('PgEmpresasIndex')->with('success', 'Empresa actualizada correctamente.');
    }

    public function Delete($id)
    {
        $empresa = PgEmpresa::where('id', $id)->firstOrFail();
        $empresa->delete();
        return redirect()->back()->with('success', 'Empresa eliminada correctamente.');
    }

    public function Restore($id)
    {
        $empresa = PgEmpresa::soloEliminados()->where('id', $id)->firstOrFail();
        $empresa->estado = null;
        $empresa->save();
        return redirect()->back()->with('success', 'Empresa restaurada correctamente.');
    }

    /**
     * Endpoint para Select2 (bambox) - permite escribir y buscar.
     */
    public function Select2(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $items = PgEmpresa::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('id', 'like', "%{$q}%")
                        ->orWhere('nombre', 'like', "%{$q}%")
                        ->orWhere('ruc', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'nombre', 'ruc']);

        $results = $items->map(function ($e) {
            $txt = $e->nombre;
            if (!empty($e->ruc)) {
                $txt .= ' - RUC: ' . $e->ruc;
            }
            $txt .= ' (' . $e->id . ')';
            return ['id' => $e->id, 'text' => $txt];
        })->values();

        return response()->json($results);
    }
}
