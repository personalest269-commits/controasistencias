<?php

namespace App\Http\Controllers;

use App\Models\PgApiConfig;
use Illuminate\Http\Request;

class ApiConfigController extends Controller
{
    /**
     * Configuración de API usada por Importación de Personas.
     */
    public function editPersonasImport()
    {
        $cfg = PgApiConfig::firstOrCreate(
            ['clave' => 'personas_import'],
            ['auth_type' => 'none', 'query_params' => ['vigente' => 'S', 'size' => 500, 'max_pages' => 200]]
        );

        return view('importaciones.api_config_personas', [
            'cfg' => $cfg,
        ]);
    }

    public function updatePersonasImport(Request $request)
    {
        $request->validate([
            'api_url' => 'nullable|url',
            'auth_type' => 'required|in:none,basic,bearer',
            'auth_user' => 'nullable|string|max:150',
            'auth_pass' => 'nullable|string|max:150',
            'auth_token' => 'nullable|string|max:500',
            // defaults
            'vigente' => 'nullable|in:S,N',
            'cod_departamento' => 'nullable|string|max:50',
            'tipo_identificacion' => 'nullable|string|max:50',
            'identificacion' => 'nullable|string|max:50',
            'size' => 'nullable|integer|min:1|max:5000',
            'max_pages' => 'nullable|integer|min:1|max:5000',
        ]);

        // Validación condicional de auth
        if ($request->auth_type === 'basic' && (empty($request->auth_user) || empty($request->auth_pass))) {
            return back()->with('error', 'Autenticación BASIC: ingresa usuario y clave.');
        }
        if ($request->auth_type === 'bearer' && empty($request->auth_token)) {
            return back()->with('error', 'Autenticación BEARER: ingresa el token.');
        }

        $cfg = PgApiConfig::firstOrCreate(['clave' => 'personas_import']);

        $cfg->api_url = $request->api_url;
        $cfg->auth_type = $request->auth_type;
        $cfg->auth_user = $request->auth_user;
        $cfg->auth_pass = $request->auth_pass;
        $cfg->auth_token = $request->auth_token;

        $cfg->query_params = array_filter([
            'vigente' => $request->input('vigente', 'S'),
            'cod_departamento' => $request->input('cod_departamento'),
            'tipo_identificacion' => $request->input('tipo_identificacion'),
            'identificacion' => $request->input('identificacion'),
            'size' => $request->input('size', 500),
            'max_pages' => $request->input('max_pages', 200),
        ], fn ($v) => $v !== null && $v !== '');

        $cfg->save();

        return back()->with('success', 'Configuración de API guardada.');
    }
}
