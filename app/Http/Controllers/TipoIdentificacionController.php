<?php

namespace App\Http\Controllers;

use App\Models\PgTipoIdentificacion;
use Illuminate\Http\Request;

class TipoIdentificacionController extends Controller
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

        $query = PgTipoIdentificacion::orderBy('codigo');

        if ($soloEliminados) {
            $query = PgTipoIdentificacion::soloEliminados()->orderBy('codigo');
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('codigo', 'like', "%{$q}%")
                  ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        $registros = $query->paginate(20)->appends($request->query());

        return view('TipoIdentificacion.index', [
            'registros' => $registros,
            'soloEliminados' => $soloEliminados,
            'q' => $q,
        ]);
    }

    public function Store(Request $request)
    {
        $request->validate([
            'codigo' => ['required', 'string', 'max:5', 'unique:pg_tipo_identificacion,codigo'],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado_actual' => ['nullable', 'integer'],
            'asocia_persona' => ['nullable', 'integer'],
            'validar' => ['nullable', 'integer'],
            'longitud' => ['nullable', 'integer'],
            'longitud_fija' => ['nullable', 'integer'],
            'codigo_sri' => ['nullable', 'string', 'max:10'],
        ]);

        $tipo = new PgTipoIdentificacion();
        $tipo->codigo = $request->codigo;
        $tipo->descripcion = $request->descripcion;
        $tipo->estado_actual = (int) ($request->input('estado_actual', 1));
        $tipo->asocia_persona = (int) ($request->input('asocia_persona', 0));
        $tipo->validar = (int) ($request->input('validar', 0));
        $tipo->longitud = $request->filled('longitud') ? (int)$request->longitud : null;
        $tipo->longitud_fija = (int) ($request->input('longitud_fija', 0));
        $tipo->codigo_sri = $request->filled('codigo_sri') ? $request->codigo_sri : null;
        $tipo->estado = null;
        $tipo->save();

        return redirect()->route('TipoIdentificacionIndex')->with('success', 'Registro creado correctamente.');
    }

    public function Edit($id)
    {
        $registro = PgTipoIdentificacion::conEliminados()->where('id', $id)->firstOrFail();

        return view('TipoIdentificacion.edit', [
            'registro' => $registro,
        ]);
    }

    public function Update(Request $request, $id)
    {
        $registro = PgTipoIdentificacion::conEliminados()->where('id', $id)->firstOrFail();

        $request->validate([
            'codigo' => ['required', 'string', 'max:5', 'unique:pg_tipo_identificacion,codigo,' . $registro->id],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado_actual' => ['nullable', 'integer'],
            'asocia_persona' => ['nullable', 'integer'],
            'validar' => ['nullable', 'integer'],
            'longitud' => ['nullable', 'integer'],
            'longitud_fija' => ['nullable', 'integer'],
            'codigo_sri' => ['nullable', 'string', 'max:10'],
        ]);

        $registro->codigo = $request->codigo;
        $registro->descripcion = $request->descripcion;
        $registro->estado_actual = (int) ($request->input('estado_actual', 1));
        $registro->asocia_persona = (int) ($request->input('asocia_persona', 0));
        $registro->validar = (int) ($request->input('validar', 0));
        $registro->longitud = $request->filled('longitud') ? (int)$request->longitud : null;
        $registro->longitud_fija = (int) ($request->input('longitud_fija', 0));
        $registro->codigo_sri = $request->filled('codigo_sri') ? $request->codigo_sri : null;
        $registro->save();

        return redirect()->route('TipoIdentificacionEdit', $registro->id)->with('success', 'Registro actualizado correctamente.');
    }

    public function Delete($id)
    {
        $registro = PgTipoIdentificacion::conEliminados()->where('id', $id)->firstOrFail();
        $registro->delete();

        return redirect()->route('TipoIdentificacionIndex')->with('success', 'Registro eliminado (lógico) correctamente.');
    }
}
