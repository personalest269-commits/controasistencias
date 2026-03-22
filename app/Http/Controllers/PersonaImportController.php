<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\PgApiConfig;
use App\Models\PgEmpresa;

class PersonaImportController extends Controller
{
    /**
     * Cache in-memory para evitar consultas repetidas durante un mismo request.
     */
    private ?array $tipoIdentificacionCodigoByDesc = null; // [DESC_NORMALIZADA => CODIGO]
    private array $personaIdByIdentificacion = []; // [IDENTIFICACION => ID_PERSONA|NULL]

    public function index()
    {
        $cfg = null;
        if (\Schema::hasTable('pg_api_config')) {
            $cfg = PgApiConfig::where('clave', 'personas_import')->first();
        }

        // Combo de empresa SOLO para guardar empresa_id en pg_persona_stg (no afecta consumo de API)
        $empresas = [];
        $defaultEmpresaId = null;
        if (\Schema::hasTable('pg_empresa')) {
            $empresas = PgEmpresa::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->toArray();

            $gadse = PgEmpresa::query()
                ->whereRaw('UPPER(nombre) = ?', ['GADSE'])
                ->first();

            $defaultEmpresaId = $gadse?->id ?? ($empresas[0]['id'] ?? null);
        }

        return view('personas.import.index', [
            'apiCfg' => $cfg,
            'empresas' => $empresas,
            'defaultEmpresaId' => $defaultEmpresaId,
        ]);
    }

    public function importXls(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:10240',
            'empresa_id' => 'nullable|string|max:10',
        ]);

        $batch = (string) Str::uuid();

        $empresaIdResolved = (string) $request->input('empresa_id', '');
        if ($empresaIdResolved === '' && \Schema::hasTable('pg_empresa')) {
            $gadse = PgEmpresa::query()->whereRaw('UPPER(nombre) = ?', ['GADSE'])->first();
            $empresaIdResolved = $gadse?->id ?? '';
        }
        if ($empresaIdResolved === '') $empresaIdResolved = null;

        // Registrar el lote (batch) para que el flujo sea: TEMPORAL -> PREVIEW -> APLICAR
        DB::table('pg_importacion_batches')->insert([
            'batch_id' => $batch,
            'empresa_id' => $empresaIdResolved, // ✅ FIX

            'fuente' => 'XLS',
            'archivo_nombre' => $request->file('file')->getClientOriginalName(),
            'user_id' => auth()->id(),
            'estado' => 'PREVIEW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return back()->with('error', 'El archivo no contiene registros.');
        }

        // Encabezados (fila 1)
        $headerRow = array_shift($rows);
        $headers = [];
        foreach ($headerRow as $col => $name) {
            $h = strtoupper(trim((string) $name));
            if ($h === 'NONBRES') $h = 'NOMBRES'; // corrección del XLS
            $headers[$col] = $h;
        }

        // Mapa de empresas por NOMBRE (para resolver empresa_id desde la columna EMPRESA del XLS)
        $empresaMap = [];
        if (\Schema::hasTable('pg_empresa')) {
            $empresaMap = PgEmpresa::query()
                ->get(['id','nombre'])
                ->mapWithKeys(function ($e) {
                    return [$this->normalizeKey($e->nombre) => (string)$e->id];
                })
                ->toArray();
        }

        $payload = [];
        foreach ($rows as $r) {
            // Construir array asociativo por nombre de columna
            $data = [];
            foreach ($headers as $col => $h) {
                $data[$h] = isset($r[$col]) ? trim((string) $r[$col]) : null;
            }

            // Solo guardar vigentes S (si viene vacío, NO se guarda)
            $vigente = strtoupper((string) ($data['VIGENTE'] ?? ''));
            if ($vigente !== 'S') {
                continue;
            }

            // Resolver empresa por fila (columna EMPRESA). Si no viene, usa la empresa seleccionada.
            $empresaIdFila = $empresaIdResolved;
            $empresaNombre = $data['EMPRESA'] ?? null;
            $kEmp = $this->normalizeKey($empresaNombre);
            if ($kEmp !== '' && isset($empresaMap[$kEmp])) {
                $empresaIdFila = $empresaMap[$kEmp];
            } elseif ($kEmp !== '') {
                // viene empresa pero no existe en catálogo => dejar null para que el preview marque error
                $empresaIdFila = null;
            }

            $payload[] = $this->mapToStaging($batch, $data, null, $empresaIdFila);

            // Insert por chunks
            if (count($payload) >= 500) {
                DB::table('pg_persona_stg')->insert($payload);
                $payload = [];
            }
        }

        if (!empty($payload)) {
            DB::table('pg_persona_stg')->insert($payload);
        }

        return redirect()->route('personas.import.preview', $batch)
            ->with('success', 'Archivo cargado. Revisa el preview antes de aplicar.');
    }

    public function importApi(Request $request)
    {
        $request->validate([
            'api_url' => 'nullable|url',
            'auth_type' => 'nullable|in:none,basic,bearer',
            'auth_user' => 'nullable|string|max:150',
            'auth_pass' => 'nullable|string|max:150',
            'auth_token' => 'nullable|string|max:500',

            'vigente' => 'nullable|in:S,N',
            'cod_departamento' => 'nullable|string|max:50',
            'tipo_identificacion' => 'nullable|string|max:50',
            'identificacion' => 'nullable|string|max:50',
            'size' => 'nullable|integer|min:1|max:5000',
            'max_pages' => 'nullable|integer|min:1|max:5000',
            'empresa_id' => 'nullable|string|max:10',
        ]);

        // Config guardada
        $cfg = null;
        $defaults = [];
        if (\Schema::hasTable('pg_api_config')) {
            $cfg = PgApiConfig::where('clave', 'personas_import')->first();
            $defaults = ($cfg && is_array($cfg->query_params)) ? $cfg->query_params : [];
        }

        // URL final
        $apiUrl = (string) (($cfg?->api_url) ?? $request->input('api_url', ''));
        if ($apiUrl === '') {
            return back()->with('error', 'No hay URL de API configurada. Ve a Configuración API (Importación Personas).');
        }

        $batch = (string) Str::uuid();

        $empresaIdResolved = (string) $request->input('empresa_id', '');
        if ($empresaIdResolved === '' && \Schema::hasTable('pg_empresa')) {
            $gadse = PgEmpresa::query()->whereRaw('UPPER(nombre) = ?', ['GADSE'])->first();
            $empresaIdResolved = $gadse?->id ?? '';
        }
        if ($empresaIdResolved === '') $empresaIdResolved = null;

        DB::table('pg_importacion_batches')->insert([
            'batch_id' => $batch,
            'empresa_id' => $empresaIdResolved,

            'fuente' => 'API',
            'api_url' => $apiUrl,
            'user_id' => auth()->id(),
            'estado' => 'PREVIEW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cliente HTTP
        $authType = $request->input('auth_type', $cfg?->auth_type ?? 'none') ?: 'none';

        $client = Http::timeout(260)
            ->acceptJson()
            ->withHeaders(['Accept' => 'application/json']);

        if ($authType === 'basic') {
            $u = (string) $request->input('auth_user', $cfg?->auth_user ?? '');
            $p = (string) $request->input('auth_pass', $cfg?->auth_pass ?? '');
            if ($u === '' || $p === '') {
                return back()->with('error', 'Autenticación BASIC: ingresa usuario y clave.');
            }
            $client = $client->withBasicAuth($u, $p);
        } elseif ($authType === 'bearer') {
            $t = (string) $request->input('auth_token', $cfg?->auth_token ?? '');
            if ($t === '') {
                return back()->with('error', 'Autenticación BEARER: ingresa el token.');
            }
            $client = $client->withToken($t);
        }

        // size solicitado
        $resolvedSize = $request->input('size', $defaults['size'] ?? ($cfg?->size ?? 700));
        if ($resolvedSize === null || $resolvedSize === '') $resolvedSize = 700;

        // Query base
        $query = array_filter([
            'vigente' => $request->input('vigente', $defaults['vigente'] ?? 'S'),
            'cod_departamento' => $request->input('cod_departamento', $defaults['cod_departamento'] ?? null),
            'tipo_identificacion' => $request->input('tipo_identificacion', $defaults['tipo_identificacion'] ?? null),
            'identificacion' => $request->input('identificacion', $defaults['identificacion'] ?? null),
            'size' => (int) $resolvedSize,
        ], fn($v) => $v !== null && $v !== '');

        // Máx páginas
        $resolvedMaxPages = $request->input('max_pages', $defaults['max_pages'] ?? 200);
        $resolvedMaxPages = (int) $resolvedMaxPages;
        if ($resolvedMaxPages <= 0) $resolvedMaxPages = 200;
        if ($resolvedMaxPages > 5000) $resolvedMaxPages = 5000;

        // Helpers
        $extractItems = function ($json) {
            if (is_array($json) && isset($json['data']) && is_array($json['data'])) return $json['data'];
            if (is_array($json) && array_is_list($json)) return $json; // tu caso
            return null;
        };

        $detectTotalPages = function ($json) {
            if (!is_array($json) || array_is_list($json)) return null;
            if (isset($json['totalPages'])) return (int)$json['totalPages'];
            if (isset($json['last_page'])) return (int)$json['last_page'];
            if (isset($json['total_pages'])) return (int)$json['total_pages'];
            if (isset($json['total']) && isset($json['size']) && (int)$json['size'] > 0) {
                return (int) ceil(((int)$json['total']) / ((int)$json['size']));
            }
            return null;
        };

        // Retry/backoff 429
        $fetch = function (array $q) use ($client, $apiUrl) {
            $tries = 0;
            while (true) {
                $resp = $client->get($apiUrl, $q);
                if ($resp->status() !== 429) return $resp;

                $tries++;
                if ($tries > 6) return $resp;

                $ra = $resp->header('Retry-After');
                if ($ra !== null && is_numeric($ra)) {
                    sleep((int)$ra);
                } else {
                    sleep((int) pow(2, $tries - 1));
                }
            }
        };

        $payload = [];
        $inserted = 0;

        $processItems = function (array $items) use (&$payload, &$inserted, $batch, $empresaIdResolved) {
            foreach ($items as $item) {
                if (!is_array($item)) continue;

                $data = [];
                foreach ($item as $k => $v) {
                    $key = strtoupper(trim((string) $k));
                    if ($key === 'NONBRES') $key = 'NOMBRES';
                    $data[$key] = is_string($v) ? trim($v) : $v;
                }

                $vigente = strtoupper((string) ($data['VIGENTE'] ?? ''));
                if ($vigente !== 'S') continue;

                $payload[] = $this->mapToStaging($batch, $data, $item, $empresaIdResolved);

                if (count($payload) >= 500) {
                    DB::table('pg_persona_stg')->insert($payload);
                    $inserted += count($payload);
                    $payload = [];
                }
            }
        };

        // =========================
        // PAGE 1
        // =========================
        $firstQuery = $query;
        $firstQuery['page'] = 1;

        $resp = $fetch($firstQuery);
        if (!$resp->ok()) {
            return back()->with('error', 'No se pudo consultar la API. Código: ' . $resp->status());
        }

        $jsonFirst = $resp->json();
        $itemsFirst = $extractItems($jsonFirst);

        if (!is_array($itemsFirst)) {
            return back()->with('error', 'La respuesta de la API no tiene un arreglo de datos esperado.');
        }

        $totalPages = $detectTotalPages($jsonFirst);

        $serverPageSize = count($itemsFirst);
        if ($serverPageSize <= 0) $serverPageSize = 50;

        \Log::info('IMPORT PAGINATION SETTINGS', [
            'max_pages' => $resolvedMaxPages,
            'server_page_size' => $serverPageSize,
            'query_size_sent' => $query['size'] ?? null,
        ]);

        $processItems($itemsFirst);

        // =========================
        // MODO 1: con totalPages
        // =========================
        if ($totalPages !== null && $totalPages >= 2) {
            $totalPages = min($totalPages, 2000);

            for ($page = 2; $page <= $totalPages; $page++) {
                $pageQuery = $query;
                $pageQuery['page'] = $page;

                $resp = $fetch($pageQuery);
                if (!$resp->ok()) {
                    return back()->with('error', "Error consultando API en page={$page}. Código: " . $resp->status());
                }

                $json = $resp->json();
                $items = $extractItems($json);

                if (!is_array($items) || count($items) === 0) break;

                $processItems($items);
            }
        }
        // =========================
        // MODO 2: sin totalPages
        // =========================
        else {
            $maxPages = $resolvedMaxPages;
            $throttleUs = 250000; // 0.25s

            // firma page=1
            $first1 = $itemsFirst[0] ?? null;
            $last1  = end($itemsFirst);

            $firstId1 = is_array($first1) ? ($first1['ID'] ?? $first1['id'] ?? null) : null;
            $lastId1  = is_array($last1)  ? ($last1['ID'] ?? $last1['id'] ?? null)  : null;
            if (is_string($firstId1)) $firstId1 = trim($firstId1);
            if (is_string($lastId1))  $lastId1  = trim($lastId1);

            $paginationMode = null;
            $modesToTry = ['page', 'offset', 'start', 'skip'];

            $buildQuery = function (string $mode, int $page) use ($query, $serverPageSize) {
                $q = $query;

                if ($mode === 'page') {
                    $q['page'] = $page;
                    return $q;
                }

                $offset = ($page - 1) * $serverPageSize;

                if ($mode === 'offset') {
                    $q['offset'] = $offset;
                    $q['limit']  = $serverPageSize;
                    $q['page']   = $page;
                    return $q;
                }

                if ($mode === 'start') {
                    $q['start'] = $offset;
                    $q['limit'] = $serverPageSize;
                    $q['page']  = $page;
                    return $q;
                }

                // skip
                $q['skip'] = $offset;
                $q['take'] = $serverPageSize;
                $q['page'] = $page;
                return $q;
            };

            // Detectar modo en page=2
            $page = 2;
            foreach ($modesToTry as $mode) {
                $q2 = $buildQuery($mode, 2);

                $resp2 = $fetch($q2);
                if (!$resp2->ok()) continue;

                $items2 = $extractItems($resp2->json());
                if (!is_array($items2) || count($items2) === 0) continue;

                $first2 = $items2[0] ?? null;
                $last2  = end($items2);

                $firstId2 = is_array($first2) ? ($first2['ID'] ?? $first2['id'] ?? null) : null;
                $lastId2  = is_array($last2)  ? ($last2['ID'] ?? $last2['id'] ?? null)  : null;
                if (is_string($firstId2)) $firstId2 = trim($firstId2);
                if (is_string($lastId2))  $lastId2  = trim($lastId2);

                \Log::info('API PAGE CHECK', [
                    'mode' => $mode,
                    'page' => 2,
                    'count' => count($items2),
                    'first_id' => $firstId2,
                    'last_id' => $lastId2,
                    'final_url' => $apiUrl . '?' . http_build_query($q2),
                ]);

                // Si cambia vs page=1, este modo pagina de verdad
                if ($firstId2 !== $firstId1 || $lastId2 !== $lastId1) {
                    $paginationMode = $mode;
                    $processItems($items2);
                    $page = 3;
                    usleep($throttleUs);
                    break;
                }
            }

            if ($paginationMode === null) {
                return back()->with(
                    'error',
                    'La API está devolviendo siempre los mismos 50 registros y no está paginando con page/offset/start/skip. ' .
                    'Confirma el parámetro correcto de paginación o usa un endpoint que devuelva totalPages.'
                );
            }

            // Continuar page >= 3 con el modo elegido
            while ($page <= $maxPages) {
                $q = $buildQuery($paginationMode, $page);

                $respP = $fetch($q);
                if (!$respP->ok()) {
                    return back()->with('error', "Error consultando API en page={$page}. Código: " . $respP->status());
                }

                $itemsP = $extractItems($respP->json());
                if (!is_array($itemsP) || count($itemsP) === 0) break;

                $firstP = $itemsP[0] ?? null;
                $lastP  = end($itemsP);

                $firstIdP = is_array($firstP) ? ($firstP['ID'] ?? $firstP['id'] ?? null) : null;
                $lastIdP  = is_array($lastP)  ? ($lastP['ID'] ?? $lastP['id'] ?? null)  : null;
                if (is_string($firstIdP)) $firstIdP = trim($firstIdP);
                if (is_string($lastIdP))  $lastIdP  = trim($lastIdP);

                \Log::info('API PAGE CHECK', [
                    'mode' => $paginationMode,
                    'page' => $page,
                    'count' => count($itemsP),
                    'first_id' => $firstIdP,
                    'last_id' => $lastIdP,
                ]);

                $processItems($itemsP);

                if (count($itemsP) < $serverPageSize) break;

                $page++;
                usleep($throttleUs);
            }
        }

        // Flush final
        if (!empty($payload)) {
            DB::table('pg_persona_stg')->insert($payload);
            $inserted += count($payload);
            $payload = [];
        }

        DB::table('pg_importacion_batches')->where('batch_id', $batch)->update([
            'total_registros' => $inserted,
            'total_vigentes' => $inserted,
            'updated_at' => now(),
        ]);

        return redirect()->route('personas.import.preview', $batch)
            ->with('success', 'Datos de API cargados en tabla temporal (' . $inserted . '). Revisa el preview antes de aplicar.');
    }

    public function preview(string $batch)
    {
        $all = (int) request()->query('all', 0) === 1;

        $perPage = $all ? 10000 : (int) request()->query('per_page', 50);
        if ($perPage <= 0) $perPage = 50;
        if ($perPage > 10000) $perPage = 10000;

        $rows = DB::table('pg_persona_stg as s')
            ->leftJoin('pg_persona as p', 'p.identificacion', '=', 's.identificacion')
            ->leftJoin('pg_estado_civil as ec', DB::raw('UPPER(TRIM(ec.descripcion))'), '=', DB::raw('UPPER(TRIM(s.descripcion_estado_civil))'))
            ->leftJoin('pg_departamento as d', 'd.codigo', '=', 's.cod_departamento')
            ->leftJoin('pg_empresa as e', 'e.id', '=', 's.empresa_id')
            ->where('s.batch_id', $batch)
            ->where('s.vigente', 'S')
            ->select([
                's.identificacion',
                's.tipo',
                's.nombres', 's.apellido1', 's.apellido2',
                's.direccion',
                's.vigencia_desde', 's.vigencia_hasta',
                's.departamento',
                's.email', 's.email_laboral',
                's.fecha_nacimiento',
                's.tipo_identificacion',
                's.sexo',
                's.celular',
                's.fecha_ingreso',
                's.empresa_id',
                DB::raw('e.nombre as empresa_nombre'),

                's.descripcion_estado_civil',
                DB::raw('ec.codigo as cod_estado_civil_resuelto'),
                's.cod_departamento',
                DB::raw('d.id as departamento_id_resuelto'),
                DB::raw("CASE WHEN p.id IS NULL THEN 'INSERT' ELSE 'UPDATE' END as accion"),
                // Estado civil / departamento pueden quedar NULL (no bloquean aplicar)
                DB::raw("CASE WHEN TRIM(COALESCE(s.descripcion_estado_civil,'')) = '' THEN 'OK' WHEN ec.codigo IS NULL THEN 'NULL' ELSE 'OK' END as estado_civil_check"),
                DB::raw("CASE WHEN TRIM(COALESCE(s.cod_departamento,'')) = '' THEN 'OK' WHEN d.id IS NULL THEN 'NULL' ELSE 'OK' END as departamento_check"),
                DB::raw("CASE WHEN e.id IS NULL THEN 'NO_MAP_EMPRESA' ELSE 'OK' END as empresa_check"),
            ])
            ->orderBy('s.identificacion')
            ->paginate($perPage)
            ->appends(request()->query());

        // Solo EMPRESA bloquea aplicar
        $hasErrors = DB::table('pg_persona_stg as s')
            ->leftJoin('pg_empresa as e', 'e.id', '=', 's.empresa_id')
            ->where('s.batch_id', $batch)
            ->where('s.vigente', 'S')
            ->whereNull('e.id')
            ->exists();

        return view('personas.import.preview', [
            'batch' => $batch,
            'rows' => $rows,
            'hasErrors' => $hasErrors,
        ]);
    }

    public function report(string $batch)
    {
        $stg = DB::table('pg_persona_stg')
            ->where('batch_id', $batch)
            ->where('vigente', 'S')
            ->get();

        $db = DB::table('pg_persona')
            ->select('id', 'identificacion', 'tipo', 'nombres', 'apellido1', 'apellido2', 'direccion', 'fecha_nacimiento', 'tipo_identificacion', 'sexo', 'celular', 'email', 'cod_estado_civil', 'departamento_id', 'estado', 'vigente')
            ->get()
            ->keyBy('identificacion');

        $rowsOut = [];
        $rowsOut[] = ['tipo', 'identificacion', 'detalle'];

        foreach ($stg as $r) {
            $id = (string)($r->identificacion ?? '');
            if ($id === '') continue;

            if (!isset($db[$id])) {
                $rowsOut[] = ['NUEVO', (string)$id, 'no existe en pg_persona'];
                continue;
            }

            $p = $db[$id];

            $dbVigente = strtoupper((string)($p->vigente ?? ''));
            $dbEstado  = strtoupper((string)($p->estado ?? ''));
            $isReactivar = ($dbVigente !== 'S') || ($dbEstado === 'X');

            $diff = [];
            foreach (['tipo','nombres','apellido1','apellido2','direccion','fecha_nacimiento','tipo_identificacion','sexo','celular'] as $f) {
                $a = (string)($p->$f ?? '');
                $b = (string)($r->$f ?? '');
                if ($a !== $b) {
                    $diff[$f] = ['db' => $p->$f, 'api' => $r->$f];
                }
            }

            if ($isReactivar) {
                $detalle = 'vigente=S, estado=NULL';
                if (!empty($diff)) {
                    $detalle .= ' | cambios: ' . json_encode($diff, JSON_UNESCAPED_UNICODE);
                }
                $rowsOut[] = ['ACTIVAR', (string)$id, $detalle];
            } elseif (!empty($diff)) {
                $rowsOut[] = ['ACTUALIZA', (string)$id, json_encode($diff, JSON_UNESCAPED_UNICODE)];
            }
        }

        // Desactivar: vigentes en DB que no están en el lote
        $missing = DB::table('pg_persona')
            ->where('vigente', 'S')
            ->whereNotIn('identificacion', function ($q) use ($batch) {
                $q->select('identificacion')
                    ->from('pg_persona_stg')
                    ->where('batch_id', $batch)
                    ->where('vigente', 'S');
            })
            ->pluck('identificacion')
            ->all();

        foreach ($missing as $id) {
            $rowsOut[] = ['DESACTIVA', (string)$id, 'vigente=N, estado=X'];
        }

        // ====== Generar XLSX con colores ======
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('reporte_cambios');

        $rowIdx = 1;
        foreach ($rowsOut as $row) {
            $sheet->setCellValue('A'.$rowIdx, $row[0] ?? '');
            $sheet->setCellValue('B'.$rowIdx, $row[1] ?? '');
            $sheet->setCellValue('C'.$rowIdx, $row[2] ?? '');
            $rowIdx++;
        }

        $headerRange = 'A1:C1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
        $sheet->freezePane('A2');

        $lastRow = count($rowsOut);
        $sheet->getStyle('A1:C'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (['A','B','C'] as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        for ($r = 2; $r <= $lastRow; $r++) {
            $tipo = (string) $sheet->getCell('A'.$r)->getValue();
            if ($tipo === 'NUEVO' || $tipo === 'ACTIVAR') {
                $sheet->getStyle('A'.$r.':C'.$r)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFC6EFCE');
            }
            if ($tipo === 'ACTUALIZA') {
                $sheet->getStyle('A'.$r.':C'.$r)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF2CC');
            }
            if ($tipo === 'DESACTIVA') {
                $sheet->getStyle('A'.$r.':C'.$r)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFC7CE');
            }
        }

        $fn = 'reporte_cambios_'.$batch.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fn, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function apply(string $batch)
    {
        try {
            DB::transaction(function () use ($batch) {
                $userId = auth()->id();

                // Leer el batch para saber la fuente (XLS / API)
                $batchRow = DB::table('pg_importacion_batches')->where('batch_id', $batch)->first();
                $fuente = $batchRow->fuente ?? 'XLS';

                // ✅ FIX CLAVE: empresa_id se toma del batch
                $empresaId = $batchRow->empresa_id ?? null;

                // Cargar staging con mapeos resueltos (estado civil / departamento)
                $rows = DB::table('pg_persona_stg as s')
                    ->leftJoin('pg_estado_civil as ec', DB::raw("UPPER(TRIM(ec.descripcion))"), '=', DB::raw("UPPER(TRIM(s.descripcion_estado_civil))"))
                    ->leftJoin('pg_departamento as d', 'd.codigo', '=', 's.cod_departamento')
            ->leftJoin('pg_empresa as e', 'e.id', '=', 's.empresa_id')
                    ->where('s.batch_id', $batch)
                    ->where('s.vigente', 'S')
                    ->select(
                        's.*',
                        DB::raw('ec.codigo as cod_estado_civil_resuelto'),
                        DB::raw('d.id as departamento_id_resuelto')
                    )
                    ->get();

                $totalRegistros = $rows->count();
                $totalVigentes = $totalRegistros;

                $totalInsert = 0;
                $totalUpdate = 0;
                $totalErrores = 0;

                foreach ($rows as $r) {
                    // Validación: EMPRESA es obligatoria. Estado civil/departamento pueden quedar NULL.
                    if (empty($r->empresa_id)) {
                        $totalErrores++;
                        DB::table('pg_importacion_logs')->insert([
                            'batch_id' => $batch,
                            'empresa_id' => $empresaId,
                            'identificacion' => $r->identificacion,
                            'accion' => 'ERROR',
                            'mensaje_error' => 'NO_MAP_EMPRESA',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        continue;
                    }

                    $existing = DB::table('pg_persona')->where('identificacion', $r->identificacion)->first();

                    $payload = [
	                        // Prioridad: empresa por fila (stg.empresa_id). Si no existe, ya se marcó error arriba.
	                        'empresa_id' => ($r->empresa_id ?? $empresaId ?? null),
                        'tipo' => $r->tipo,
                        'nombres' => $r->nombres,
                        'apellido1' => $r->apellido1,
                        'apellido2' => $r->apellido2,
                        'direccion' => $r->direccion,
                        'fecha_nacimiento' => $r->fecha_nacimiento,
                        'tipo_identificacion' => $r->tipo_identificacion,
                        'identificacion' => $r->identificacion,
                        'sexo' => $r->sexo,
                        'celular' => $r->celular,
                        'email' => (!empty($r->email) ? $r->email : (!empty($r->email_laboral) ? $r->email_laboral : null)),
                        // Permitido NULL
                        'cod_estado_civil' => $r->cod_estado_civil_resuelto ?? null,
                        'departamento_id' => $r->departamento_id_resuelto ?? null,
                        'vigente' => 'S',
                        'estado' => null,
                        'updated_at' => now(),
                    ];

                    if ($existing) {
                        $before = [
                            'id' => $existing->id ?? null,
	                            'empresa_id' => $existing->empresa_id ?? null,
                            'tipo' => $existing->tipo ?? null,
                            'nombres' => $existing->nombres ?? null,
                            'apellido1' => $existing->apellido1 ?? null,
                            'apellido2' => $existing->apellido2 ?? null,
                            'direccion' => $existing->direccion ?? null,
                            'fecha_nacimiento' => $existing->fecha_nacimiento ?? null,
                            'tipo_identificacion' => $existing->tipo_identificacion ?? null,
                            'identificacion' => $existing->identificacion ?? null,
                            'sexo' => $existing->sexo ?? null,
                            'celular' => $existing->celular ?? null,
                            'email' => $existing->email ?? null,
                            'cod_estado_civil' => $existing->cod_estado_civil ?? null,
                            'departamento_id' => $existing->departamento_id ?? null,
                            'vigente' => $existing->vigente ?? null,
                        ];

                        DB::table('pg_persona')->where('id', $existing->id)->update($payload);

                        $after = DB::table('pg_persona')->where('id', $existing->id)->first();

                        DB::table('pg_importacion_logs')->insert([
                            'batch_id' => $batch,
                            'empresa_id' => $empresaId,
                            'identificacion' => $r->identificacion,
                            'persona_id' => $existing->id,
                            'accion' => 'UPDATE',
                            'before_json' => json_encode($before, JSON_UNESCAPED_UNICODE),
                            'after_json' => json_encode($after, JSON_UNESCAPED_UNICODE),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $totalUpdate++;
                    } else {
                        // INSERT: mandar id vacío para que el trigger genere el ID
                        $insertPayload = $payload;
                        $insertPayload['id'] = '';
                        $insertPayload['created_at'] = now();

                        DB::table('pg_persona')->insert($insertPayload);

                        $newIdRow = DB::selectOne("SELECT @last_persona_id as id");
                        $newId = $newIdRow->id ?? null;

                        $after = null;
                        if (!empty($newId)) {
                            $after = DB::table('pg_persona')->where('id', $newId)->first();
                        }
                        if (!$after) {
                            $after = DB::table('pg_persona')->where('identificacion', $r->identificacion)->orderByDesc('id')->first();
                        }

                        DB::table('pg_importacion_logs')->insert([
                            'batch_id' => $batch,
                            'empresa_id' => $empresaId,
                            'identificacion' => $r->identificacion,
                            'persona_id' => $after->id ?? $newId,
                            'accion' => 'INSERT',
                            'after_json' => json_encode($after, JSON_UNESCAPED_UNICODE),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $totalInsert++;
                    }
                }

                // Actualizar resumen del batch
                DB::table('pg_importacion_batches')->where('batch_id', $batch)->update([
                    'estado' => 'APLICADO',
                    'total_registros' => $totalRegistros,
                    'total_vigentes' => $totalVigentes,
                    'total_insert' => $totalInsert,
                    'total_update' => $totalUpdate,
                    'total_errores' => $totalErrores,
                    'aplicado_at' => now(),
                    'updated_at' => now(),
                ]);

                // SYNC: marcar como NO vigente (N) y estado = X a quienes NO vienen en el batch actual.
                $hacerSync = in_array($fuente, ['XLS', 'API'], true);

                if ($hacerSync) {
                    $toDeactivate = DB::table('pg_persona')
                        ->where('vigente', 'S')
                        ->whereNotIn('identificacion', function ($q) use ($batch) {
                            $q->select('identificacion')
                                ->from('pg_persona_stg')
                                ->where('batch_id', $batch)
                                ->where('vigente', 'S');
                        })
                        ->get();

                    $totalBajas = 0;

                    foreach ($toDeactivate as $p) {
                        $before = [
                            'id' => $p->id ?? null,
                            'tipo' => $p->tipo ?? null,
                            'nombres' => $p->nombres ?? null,
                            'apellido1' => $p->apellido1 ?? null,
                            'apellido2' => $p->apellido2 ?? null,
                            'direccion' => $p->direccion ?? null,
                            'fecha_nacimiento' => $p->fecha_nacimiento ?? null,
                            'tipo_identificacion' => $p->tipo_identificacion ?? null,
                            'identificacion' => $p->identificacion ?? null,
                            'sexo' => $p->sexo ?? null,
                            'celular' => $p->celular ?? null,
                            'email' => $p->email ?? null,
                            'cod_estado_civil' => $p->cod_estado_civil ?? null,
                            'departamento_id' => $p->departamento_id ?? null,
                            'vigente' => $p->vigente ?? null,
                        ];

                        DB::table('pg_persona')->where('id', $p->id)->update([
                            'vigente' => 'N',
                            'estado' => 'X',
                            'updated_at' => now(),
                        ]);

                        $after = DB::table('pg_persona')->where('id', $p->id)->first();

                        DB::table('pg_importacion_logs')->insert([
                            'batch_id' => $batch,
                            'empresa_id' => $empresaId,
                            'identificacion' => $p->identificacion,
                            'persona_id' => $p->id,
                            'accion' => 'UPDATE',
                            'mensaje_error' => 'AUTO_VIGENTE_N',
                            'before_json' => json_encode($before, JSON_UNESCAPED_UNICODE),
                            'after_json' => json_encode($after, JSON_UNESCAPED_UNICODE),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $totalBajas++;
                        $totalUpdate++;
                    }

                    // guardar bajas en batch (si existe la columna)
                    try {
                        DB::table('pg_importacion_batches')->where('batch_id', $batch)->update([
                            'total_bajas' => $totalBajas,
                            'updated_at' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        // si no existe la columna total_bajas, ignorar
                    }
                }

                // Limpieza automática del staging del batch (recomendado)
                DB::table('pg_persona_stg')->where('batch_id', $batch)->delete();
            });

            return redirect()->back()->with('success', 'Importación aplicada correctamente.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error al aplicar importación: ' . $e->getMessage());
        }
    }

    public function clear(string $batch)
    {
        DB::table('pg_persona_stg')->where('batch_id', $batch)->delete();
        return redirect()->route('personas.import.index')->with('success', 'Lote eliminado.');
    }

    public function truncateStaging()
    {
        try {
            DB::statement('TRUNCATE TABLE pg_persona_stg');
            return redirect()->back()->with('success', 'Tabla temporal (pg_persona_stg) truncada correctamente.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'No se pudo truncar pg_persona_stg: ' . $e->getMessage());
        }
    }

     
    private function normalizeKey(?string $s): string
    {
        $s = trim((string)($s ?? ''));
        if ($s === '') return '';
        // quitar tildes y caracteres raros
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false && $t !== null) $s = $t;
        $s = strtoupper($s);
        // colapsar espacios
        $s = preg_replace('/\s+/', ' ', $s);
        $s = trim($s);
        return $s;
    }

function mapToStaging(string $batch, array $data, ?array $raw, ?string $empresaId = null): array
    {
        $get = function (string $key) use ($data) {
            return $data[$key] ?? null;
        };

        // Reglas solicitadas:
        // - NO considerar TIPO del XLS/API (pg_persona maneja default)
        // - NO considerar TIPO_IDENTIFICACION del XLS; se resuelve por DESCRIPCION_IDENTIFICACION -> pg_tipo_identificacion.codigo
        // - id_persona se resuelve por IDENTIFICACION contra pg_persona.identificacion (solo vigentes). Si no existe => NULL (registro nuevo)

        $identificacion = trim((string) ($get('IDENTIFICACION') ?? ''));
        $vigenteFila = strtoupper(trim((string) ($get('VIGENTE') ?? '')));

        $idPersona = null;
        if ($identificacion !== '' && $vigenteFila === 'S') {
            $idPersona = $this->lookupPersonaIdByIdentificacion($identificacion);
        }

        $descripcionIdent = trim((string) ($get('DESCRIPCION_IDENTIFICACION') ?? ''));
        $tipoIdentCodigo = null;
        if ($descripcionIdent !== '') {
            $tipoIdentCodigo = $this->lookupTipoIdentificacionCodigoByDescripcion($descripcionIdent);
        }

        return [
            'batch_id' => $batch,
            'empresa_id' => $empresaId,

            'id' => $get('ID'),
            'id_persona' => $idPersona,
            'nombres' => $get('NOMBRES'),
            'apellido1' => $get('APELLIDO1'),
            'apellido2' => $get('APELLIDO2'),
            'tipo' => null,
            'direccion' => $get('DIRECCION'),
            'nombre' => $get('NOMBRE'),
            'cargo' => $get('CARGO'),
            'codigo_cargo' => $get('CODIGO_CARGO'),
            'vigencia_desde' => $this->toDate($get('VIGENCIA_DESDE')),
            'vigencia_hasta' => $this->toDate($get('VIGENCIA_HASTA')),
            'vigente' => strtoupper((string) $get('VIGENTE')),
            'cod_departamento' => $get('COD_DEPARTAMENTO'),
            'departamento' => $get('DEPARTAMENTO'),
            'email' => $get('EMAIL'),
            'id_relacion_laboral' => $get('ID_RELACION_LABORAL'),
            'cod_motivo_accion_personal' => $get('COD_MOTIVO_ACCION_PERSONAL'),
            'cod_departamento_actual' => $get('COD_DEPARTAMENTO_ACTUAL'),
            'email_laboral' => $get('EMAIL_LABORAL'),
            'codigo_puesto' => $get('CODIGO_PUESTO'),
            'responsable' => $get('RESPONSABLE'),
            'codigo_departamento_padre' => $get('CODIGO_DEPARTAMENTO_PADRE'),
            'codigo_puesto_jerarquia' => $get('CODIGO_PUESTO_JERARQUIA'),
            'identificacion' => $get('IDENTIFICACION'),
            'codigo_programa' => $get('CODIGO_PROGRAMA'),
            'descripcion_programa' => $get('DESCRIPCION_PROGRAMA'),
            'fecha_nacimiento' => $this->toDate($get('FECHA_NACIMIENTO')),
            'tipo_identificacion' => $tipoIdentCodigo,
            'descripcion_identificacion' => $get('DESCRIPCION_IDENTIFICACION'),
            'cod_estado_civil' => $get('COD_ESTADO_CIVIL'),
            'descripcion_estado_civil' => $get('DESCRIPCION_ESTADO_CIVIL'),
            'fecha_ingreso' => $this->toDate($get('FECHA_INGRESO')),
            'sexo' => $get('SEXO'),
            'celular' => $get('CELULAR'),

            'raw_json' => $raw ? json_encode($raw, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Busca el ID de persona por identificacion, solo si en BD está vigente = 'S'.
     * Devuelve NULL si no existe (registro nuevo).
     */
    private function lookupPersonaIdByIdentificacion(string $identificacion): ?string
    {
        $k = trim($identificacion);
        if ($k === '') return null;

        if (array_key_exists($k, $this->personaIdByIdentificacion)) {
            return $this->personaIdByIdentificacion[$k];
        }

        $row = DB::table('pg_persona')
            ->select('id')
            ->where('identificacion', $k)
            ->where('vigente', 'S')
            ->first();

        $id = $row?->id ? (string) $row->id : null;
        $this->personaIdByIdentificacion[$k] = $id;
        return $id;
    }

    /**
     * Resuelve el codigo de tipo_identificacion a partir de la descripcion.
     * Compara normalizando (mayúsculas + sin tildes + espacios).
     */
    private function lookupTipoIdentificacionCodigoByDescripcion(string $descripcion): ?string
    {
        if ($this->tipoIdentificacionCodigoByDesc === null) {
            $this->tipoIdentificacionCodigoByDesc = [];
            if (\Schema::hasTable('pg_tipo_identificacion')) {
                $all = DB::table('pg_tipo_identificacion')
                    ->select('codigo', 'descripcion')
                    ->get();
                foreach ($all as $r) {
                    $k = $this->normalizeKey((string) ($r->descripcion ?? ''));
                    if ($k !== '') {
                        $this->tipoIdentificacionCodigoByDesc[$k] = (string) ($r->codigo ?? '');
                    }
                }
            }
        }

        $k = $this->normalizeKey($descripcion);
        if ($k === '') return null;

        $codigo = $this->tipoIdentificacionCodigoByDesc[$k] ?? null;
        if ($codigo === '') $codigo = null;
        return $codigo;
    }

    private function toDate($value): ?string
    {
        if ($value === null || $value === '') return null;

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $s = trim((string) $value);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $s);
            if ($dt && $dt->format($fmt) === $s) {
                return $dt->format('Y-m-d');
            }
        }

        try {
            return (new \DateTime($s))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Rollback (deshacer) una importación por batch_id.
     */
    public function rollback($batch)
    {
        try {
            DB::beginTransaction();

            DB::table('pg_importacion_batches')
                ->where('batch_id', $batch)
                ->update([
                    'estado' => 'ROLLBACK',
                    'rollback_at' => now(),
                    'updated_at' => now(),
                ]);

            $logs = DB::table('pg_importacion_logs')
                ->where('batch_id', $batch)
                ->orderByDesc('id')
                ->get();

            foreach ($logs as $log) {
                if ($log->accion === 'INSERT') {
                    if (!empty($log->persona_id)) {
                        DB::table('pg_persona')->where('id', $log->persona_id)->delete();
                    } elseif (!empty($log->identificacion)) {
                        DB::table('pg_persona')->where('identificacion', $log->identificacion)->delete();
                    }
                } elseif ($log->accion === 'UPDATE') {
                    if ($log->before_json) {
                        $before = json_decode($log->before_json, true) ?: [];
                        if (!empty($before['id'])) {
                            DB::table('pg_persona')->where('id', $before['id'])->update([
                                'tipo' => $before['tipo'] ?? null,
                                'nombres' => $before['nombres'] ?? null,
                                'apellido1' => $before['apellido1'] ?? null,
                                'apellido2' => $before['apellido2'] ?? null,
                                'direccion' => $before['direccion'] ?? null,
                                'fecha_nacimiento' => $before['fecha_nacimiento'] ?? null,
                                'tipo_identificacion' => $before['tipo_identificacion'] ?? null,
                                'identificacion' => $before['identificacion'] ?? null,
                                'sexo' => $before['sexo'] ?? null,
                                'celular' => $before['celular'] ?? null,
                                'email' => $before['email'] ?? null,
                                'cod_estado_civil' => $before['cod_estado_civil'] ?? null,
                                'departamento_id' => $before['departamento_id'] ?? null,
                                'vigente' => $before['vigente'] ?? null,
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Rollback ejecutado correctamente para el batch: ' . $batch);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error en rollback: ' . $e->getMessage());
        }
    }
}