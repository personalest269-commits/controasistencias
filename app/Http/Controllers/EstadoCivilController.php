<?php

namespace App\Http\Controllers;

use App\Models\PgEstadoCivil;
use Illuminate\Http\Request;

class EstadoCivilController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        // Symfony 7.4+ deprecates Request::get(), use query/request bags explicitly.
        $soloEliminados = $request->query('eliminados') == 1;
        $q = trim((string) $request->query('q', ''));

        $query = PgEstadoCivil::orderBy('codigo');

        if ($soloEliminados) {
            $query = PgEstadoCivil::soloEliminados()->orderBy('codigo');
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('codigo', 'like', "%{$q}%")
                  ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        $registros = $query->paginate(20)->appends($request->query());

        return view('EstadoCivil.index', [
            'registros' => $registros,
            'soloEliminados' => $soloEliminados,
            'q' => $q,
        ]);
    }

    public function Store(Request $request)
    {
        $request->validate([
            'codigo' => ['required', 'string', 'max:5', 'unique:pg_estado_civil,codigo'],
            'descripcion' => ['required', 'string', 'max:255'],
        ]);

        $estado = new PgEstadoCivil();
        $estado->codigo = $request->codigo;
        $estado->descripcion = $request->descripcion;
        $estado->estado = null;
        $estado->save();

        return redirect()->route('EstadoCivilIndex')->with('success', 'Registro creado correctamente.');
    }

    public function Edit($id)
    {
        $registro = PgEstadoCivil::conEliminados()->where('id', $id)->firstOrFail();

        return view('EstadoCivil.edit', [
            'registro' => $registro,
        ]);
    }

    public function Update(Request $request, $id)
    {
        $registro = PgEstadoCivil::conEliminados()->where('id', $id)->firstOrFail();

        $request->validate([
            'codigo' => ['required', 'string', 'max:5', 'unique:pg_estado_civil,codigo,' . $registro->id],
            'descripcion' => ['required', 'string', 'max:255'],
        ]);

        $registro->codigo = $request->codigo;
        $registro->descripcion = $request->descripcion;
        $registro->save();

        return redirect()->route('EstadoCivilEdit', $registro->id)->with('success', 'Registro actualizado correctamente.');
    }

    public function Delete($id)
    {
        $registro = PgEstadoCivil::conEliminados()->where('id', $id)->firstOrFail();
        $registro->delete();

        return redirect()->route('EstadoCivilIndex')->with('success', 'Registro eliminado (lógico) correctamente.');
    }
}
