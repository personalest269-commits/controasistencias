<?php

namespace App\Http\Controllers;

use App\Models\Idioma;
use App\Models\PgGeneralTraduccion;
use App\Services\OfflineTranslatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;

class PgTraduccionesController extends Controller
{
    public $Response;

    public function __construct()
    {
        parent::__construct();
        $this->Response = new ResponseController();
    }

    public function Index()
    {
        if (!Schema::hasTable('pg_general_traduccion')) {
            return $this->Response->prepareResult(500, [], [], null, 'view', 'errors.500', 'Missing table pg_general_traduccion. Please run migrations.');
        }

        // Idiomas activos
        $idiomas = [];
        try {
            if (Schema::hasTable('pg_idiomas')) {
                $idiomas = Idioma::query()->where('activo', 1)->orderBy('por_defecto', 'desc')->orderBy('nombre')->get();
            }
        } catch (\Throwable $e) {
            $idiomas = collect();
        }

        // Traer todas las traducciones (solo activos)
        $rows = DB::table('pg_general_traduccion')
            ->select('clave', 'idioma_codigo', 'texto')
            ->whereNull('estado')
            ->orderBy('clave')
            ->get();

        $data = [];
        foreach ($rows as $r) {
            $k = (string) $r->clave;
            $l = (string) $r->idioma_codigo;
            $data[$k] = $data[$k] ?? ['clave' => $k, 'es' => '', 'en' => ''];
            if ($l === 'es') $data[$k]['es'] = (string) $r->texto;
            if ($l === 'en') $data[$k]['en'] = (string) $r->texto;
        }

        return $this->Response->prepareResult(200, [
            'idiomas' => $idiomas,
            'items' => array_values($data),
            'argos' => OfflineTranslatorService::isArgosInstalled(),
        ], [], [], 'view', 'PgTraducciones.index');
    }

    public function Guardar(Request $request)
    {
        if (!Schema::hasTable('pg_general_traduccion')) {
            return $this->Response->prepareResult(500, [], [], null, 'ajax', null, 'Missing table pg_general_traduccion.');
        }

        $validator = Validator::make($request->all(), [
            'clave' => 'required|string|max:255',
            'es' => 'nullable|string',
            'en' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->Response->prepareResult(422, [], $validator, null, 'ajax', null, 'Validation error');
        }

        $clave = trim((string) $request->input('clave'));
        $textoEs = (string) $request->input('es', '');
        $textoEn = (string) $request->input('en', '');

        DB::beginTransaction();
        try {
            $this->upsert($clave, 'es', $textoEs);
            $this->upsert($clave, 'en', $textoEn);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->Response->prepareResult(500, [], [], null, 'ajax', null, 'No se pudo guardar: ' . $e->getMessage());
        }

        return $this->Response->prepareResult(200, [], [], 'Guardado correctamente', 'ajax');
    }

    public function Eliminar(Request $request)
    {
        if (!Schema::hasTable('pg_general_traduccion')) {
            return $this->Response->prepareResult(500, [], [], null, 'ajax', null, 'Missing table pg_general_traduccion.');
        }

        $clave = trim((string) $request->input('clave'));
        if ($clave === '') {
            return $this->Response->prepareResult(422, [], [], null, 'ajax', null, 'Clave requerida');
        }

        try {
            DB::table('pg_general_traduccion')->where('clave', $clave)->update([
                'estado' => 'X',
                'updated_at' => now(),
            ]);
            return $this->Response->prepareResult(200, [], [], 'Eliminado', 'ajax');
        } catch (\Throwable $e) {
            return $this->Response->prepareResult(500, [], [], null, 'ajax', null, 'No se pudo eliminar');
        }
    }

    public function AutoTraducir(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'texto' => 'required|string',
            'from' => 'required|string|in:es,en',
            'to' => 'required|string|in:es,en',
        ]);
        if ($validator->fails()) {
            return $this->Response->prepareResult(422, [], $validator, null, 'ajax', null, 'Validation error');
        }

        $texto = (string) $request->input('texto');
        $from = (string) $request->input('from');
        $to = (string) $request->input('to');

        $out = OfflineTranslatorService::translate($texto, $from, $to);
        if ($out === null) {
            return $this->Response->prepareResult(409, [], [], null, 'ajax', null, 'Argos Translate no está disponible (o faltan paquetes de idioma).');
        }

        return $this->Response->prepareResult(200, ['texto' => $out], [], 'OK', 'ajax');
    }

    public function CambiarIdioma(string $codigo)
    {
        $codigo = strtolower(trim($codigo));
        if (!in_array($codigo, ['es', 'en'], true)) {
            $codigo = 'es';
        }
        try {
            // Validar que exista en tabla idiomas y esté activo
            if (Schema::hasTable('pg_idiomas')) {
                $exists = Idioma::query()->where('codigo', $codigo)->where('activo', 1)->exists();
                if (!$exists) {
                    $codigo = 'es';
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        session(['lang' => $codigo]);
        Cookie::queue('lang', $codigo, 60 * 24 * 365);
        return redirect()->back();
    }

    private function upsert(string $clave, string $idiomaCodigo, string $texto): void
    {
        $clave = trim($clave);
        if ($clave === '') {
            return;
        }

        // Si el texto está vacío, no insertamos (pero si existía, lo dejamos). Permite traducción parcial.
        $exists = DB::table('pg_general_traduccion')
            ->where('clave', $clave)
            ->where('idioma_codigo', $idiomaCodigo)
            ->first();

        if ($exists) {
            DB::table('pg_general_traduccion')
                ->where('clave', $clave)
                ->where('idioma_codigo', $idiomaCodigo)
                ->update([
                    'texto' => $texto,
                    'estado' => null,
                    'updated_at' => now(),
                ]);
            return;
        }

        if ($texto === '') {
            return;
        }

        DB::table('pg_general_traduccion')->insert([
            'id' => \pg_tr_next_id(),
            'clave' => $clave,
            'idioma_codigo' => $idiomaCodigo,
            'texto' => $texto,
            'estado' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
