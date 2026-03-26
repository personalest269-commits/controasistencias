<?php

namespace App\Http\Controllers;

use App\Models\PgApiConfig;
use App\Models\PgEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportacionesEmpresaController extends Controller
{
    public function index()
    {
        $cfg = null;
        if (\Schema::hasTable('pg_api_config')) {
            $cfg = PgApiConfig::where('clave', 'personas_import')->first();
        }

        [$empresaId, $empresaNombre, $isAdmin] = $this->resolveUserEmpresaContext();

        $empresas = [];
        if (\Schema::hasTable('pg_empresa')) {
            $q = PgEmpresa::query()->orderBy('nombre');
            if (!$isAdmin && $empresaId) {
                $q->where('id', $empresaId);
            }
            $empresas = $q->get(['id', 'nombre'])->toArray();
        }

        return view('Personas.import_empresa.index', [
            'apiCfg' => $cfg,
            'empresas' => $empresas,
            'defaultEmpresaId' => $empresaId,
            'empresaNombre' => $empresaNombre,
            'isAdminImport' => $isAdmin,
        ]);
    }

    public function importXls(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:10240',
            'empresa_id' => 'nullable|string|max:10',
        ]);

        [$empresaIdUsuario, $empresaNombreUsuario, $isAdmin] = $this->resolveUserEmpresaContext();
        $empresaIdForzada = $isAdmin ? ($request->input('empresa_id') ?: $empresaIdUsuario) : $empresaIdUsuario;

        if (!$empresaIdForzada) {
            return back()->with('error', 'No se pudo resolver la empresa del usuario autenticado.');
        }

        $batch = (string) Str::uuid();

        DB::table('pg_importacion_batches')->insert([
            'batch_id' => $batch,
            'empresa_id' => $empresaIdForzada,
            'fuente' => 'XLS',
            'archivo_nombre' => $request->file('file')->getClientOriginalName(),
            'user_id' => auth()->id(),
            'estado' => 'PREVIEW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $request->file('file')->getRealPath();
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return back()->with('error', 'El archivo no contiene registros.');
        }

        $headerRow = array_shift($rows);
        $headers = [];
        foreach ($headerRow as $col => $name) {
            $h = strtoupper(trim((string) $name));
            if ($h === 'NONBRES') $h = 'NOMBRES';
            $headers[$col] = $h;
        }

        $empresaMapByNombre = [];
        $empresaMapById = [];
        if (\Schema::hasTable('pg_empresa')) {
            $empresas = PgEmpresa::query()->get(['id', 'nombre']);
            foreach ($empresas as $e) {
                $empresaMapByNombre[$this->normalizeKey($e->nombre)] = (string) $e->id;
                $empresaMapById[(string) $e->id] = (string) $e->nombre;
            }
        }

        $personaImport = app(PersonaImportController::class);
        $payload = [];
        $erroresEmpresa = 0;
        $erroresEmpresaUsuario = 0;

        foreach ($rows as $r) {
            $data = [];
            foreach ($headers as $col => $h) {
                $data[$h] = isset($r[$col]) ? trim((string) $r[$col]) : null;
            }

            if (strtoupper((string) ($data['VIGENTE'] ?? '')) !== 'S') {
                continue;
            }

            $empresaNombreFila = trim((string) ($data['EMPRESA'] ?? ''));
            $empresaIdFila = $empresaIdForzada;

            if ($empresaNombreFila !== '') {
                $keyEmp = $this->normalizeKey($empresaNombreFila);
                if (!isset($empresaMapByNombre[$keyEmp])) {
                    $erroresEmpresa++;
                    continue;
                }
                $empresaIdFila = $empresaMapByNombre[$keyEmp];
            }

            if (!$isAdmin && (string) $empresaIdFila !== (string) $empresaIdUsuario) {
                $erroresEmpresaUsuario++;
                continue;
            }

            $payload[] = $personaImport->mapToStaging($batch, $data, null, $empresaIdFila);

            if (count($payload) >= 500) {
                DB::table('pg_persona_stg')->insert($payload);
                $payload = [];
            }
        }

        if (!empty($payload)) {
            DB::table('pg_persona_stg')->insert($payload);
        }

        if ($erroresEmpresa > 0 || $erroresEmpresaUsuario > 0) {
            $errores = [];
            if ($erroresEmpresa > 0) {
                $errores[] = "{$erroresEmpresa} fila(s) tienen empresa inexistente en pg_empresa.";
            }
            if ($erroresEmpresaUsuario > 0) {
                $errores[] = "{$erroresEmpresaUsuario} fila(s) no pertenecen a la empresa del usuario ({$empresaNombreUsuario}).";
            }

            return redirect()->route('importaciones_empresa.preview', $batch)
                ->with('error', implode(' ', $errores));
        }

        return redirect()->route('importaciones_empresa.preview', $batch)
            ->with('success', 'Archivo cargado en temporal por empresa. Revisa el preview antes de aplicar.');
    }

    public function preview(string $batch)
    {
        return app(PersonaImportController::class)->preview($batch);
    }

    public function apply(string $batch)
    {
        return app(PersonaImportController::class)->apply($batch);
    }

    public function clear(string $batch)
    {
        return app(PersonaImportController::class)->clear($batch);
    }

    public function downloadFormato()
    {
        [$empresaId, $empresaNombre] = $this->resolveUserEmpresaContext();

        $headers = [
            'NOMBRES','APELLIDO1','APELLIDO2','DIRECCION','VIGENTE','COD_DEPARTAMENTO','DEPARTAMENTO',
            'EMAIL','IDENTIFICACION','FECHA_NACIMIENTO','DESCRIPCION_IDENTIFICACION','SEXO','CELULAR','EMPRESA'
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $h);
        }

        $sheet->setCellValue('E2', 'S');
        if ($empresaNombre) {
            $sheet->setCellValue('N2', $empresaNombre);
        }

        $filename = 'plantilla_importacion_empresa_' . ($empresaId ?: 'general') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolveUserEmpresaContext(): array
    {
        $user = auth()->user();
        $empresaId = null;
        $empresaNombre = null;

        if ($user && !empty($user->id_persona) && \Schema::hasTable('pg_persona')) {
            $persona = DB::table('pg_persona')->where('id', $user->id_persona)->first();
            if ($persona && isset($persona->empresa_id)) {
                $empresaId = (string) ($persona->empresa_id ?? '');
            }
        }

        if ($empresaId && \Schema::hasTable('pg_empresa')) {
            $empresaNombre = (string) (DB::table('pg_empresa')->where('id', $empresaId)->value('nombre') ?? '');
        }

        $isAdmin = false;
        if ($user && method_exists($user, 'roles')) {
            try {
                $roles = $user->roles()->pluck('name')->map(fn($r) => strtolower((string) $r))->all();
                $isAdmin = collect($roles)->contains(fn($r) => str_contains($r, 'admin') || str_contains($r, 'super'));
            } catch (\Throwable $e) {
                $isAdmin = false;
            }
        }

        return [$empresaId, $empresaNombre, $isAdmin];
    }

    private function normalizeKey(?string $s): string
    {
        $s = trim((string) ($s ?? ''));
        if ($s === '') return '';
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false && $t !== null) $s = $t;
        $s = strtoupper(preg_replace('/\s+/', ' ', $s));
        return trim($s);
    }
}
