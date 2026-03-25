<?php

namespace App\Http\Controllers;

use App\Models\PgAsistenciaEvento;
use App\Models\PgConfiguracion;
use App\Models\PgDepartamento;
use App\Models\PgEvento;
use App\Models\PgJustificacionAsistencia;
use App\Models\PgPersona;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PgAsistenciaReportesController extends Controller
{
    private function parseDateInput(?string $value, string $defaultYmd): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $defaultYmd;
        }

        try {
            // Soporta: Y-m-d (HTML date/flatpickr) y d/m/Y (visual)
            if (str_contains($value, '/')) {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $defaultYmd;
        }
    }

    private function departamentosVigentes()
    {
        return PgDepartamento::query()
            ->with(['empresa:id,nombre'])
            ->whereNull('vigencia_hasta')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->orderBy('descripcion')
            ->get();
    }

    public function Index(Request $request)
    {
        $desde = $this->parseDateInput($request->input('desde'), Carbon::today()->startOfMonth()->format('Y-m-d'));
        $hasta = $this->parseDateInput($request->input('hasta'), Carbon::today()->format('Y-m-d'));
        $departamentoId = trim((string) $request->input('departamento_id'));
        $personaId = trim((string) $request->input('persona_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }
        if ($personaId === '') {
            $personaId = null;
        }

        $departamentos = $this->departamentosVigentes();

        $personasSelectQ = PgPersona::query()->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) {
            $personasSelectQ->where('departamento_id', $departamentoId);
        }
        $personasSelect = $personasSelectQ
            ->orderBy('identificacion')
            ->orderBy('apellido1')
            ->orderBy('apellido2')
            ->orderBy('nombres')
            ->get(['id', 'identificacion', 'nombres', 'apellido1', 'apellido2']);

        $personasQ = PgPersona::query()->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) {
            $personasQ->where('departamento_id', $departamentoId);
        }
        if ($personaId) {
            $personasQ->where('id', $personaId);
        }
        $personas = $personasQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        $summary = $this->buildSummary($personas, $desde, $hasta);
        $resumenDept = $this->buildResumenDepartamento($summary, $departamentos);

        return view('PgAsistencias.reportes', [
            'desde' => $desde,
            'hasta' => $hasta,
            'departamentoId' => $departamentoId,
            'personaId' => $personaId,
            'departamentos' => $departamentos,
            'personasSelect' => $personasSelect,
            'summary' => $summary,
            'resumenDept' => $resumenDept,
        ]);
    }

    public function DetallePersona(Request $request, string $personaId)
    {
        $desde = $this->parseDateInput($request->input('desde'), Carbon::today()->startOfMonth()->format('Y-m-d'));
        $hasta = $this->parseDateInput($request->input('hasta'), Carbon::today()->format('Y-m-d'));

        $persona = PgPersona::where('id', $personaId)->firstOrFail();
        $detail = $this->buildDetallePersona($persona, $desde, $hasta);

        return view('PgAsistencias.reporte_persona', [
            'persona' => $persona,
            'desde' => $desde,
            'hasta' => $hasta,
            'detail' => $detail,
        ]);
    }

    public function Export(Request $request)
    {
        $desde = $this->parseDateInput($request->input('desde'), Carbon::today()->startOfMonth()->format('Y-m-d'));
        $hasta = $this->parseDateInput($request->input('hasta'), Carbon::today()->format('Y-m-d'));
        $departamentoId = trim((string) $request->input('departamento_id'));
        $personaId = trim((string) $request->input('persona_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }
        if ($personaId === '') {
            $personaId = null;
        }

        $personasQ = PgPersona::query()->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) {
            $personasQ->where('departamento_id', $departamentoId);
        }
        if ($personaId) {
            $personasQ->where('id', $personaId);
        }
        $personas = $personasQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        $summary = $this->buildSummary($personas, $desde, $hasta);

        $lines = [];
        $lines[] = ['persona_id', 'nombre', 'departamento_id', 'convocados', 'asistidos', 'justificados', 'no_asistio'];
        foreach ($summary as $row) {
            $lines[] = [
                $row['persona_id'],
                $row['nombre'],
                $row['departamento_id'],
                $row['convocados'],
                $row['asistidos'],
                $row['justificados'],
                $row['no_asistio'],
            ];
        }

        $out = fopen('php://temp', 'r+');
        foreach ($lines as $l) {
            fputcsv($out, $l, ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $name = 'reporte_asistencia_' . $desde . '_a_' . $hasta
            . ($departamentoId ? ('_dep_' . $departamentoId) : '')
            . ($personaId ? ('_per_' . $personaId) : '')
            . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $name . '"');
    }

    public function ExportXlsResumen(Request $request)
    {
        [$desde, $hasta, $departamentoId, $departamentos, $personas] = $this->resolveFilters($request);
        $summary = $this->buildSummary($personas, $desde, $hasta);
        $resumenDept = $this->buildResumenDepartamento($summary, $departamentos);

        $logoUrl = PgConfiguracion::reportLogoUrl();
        $nombreSistema = (string) PgConfiguracion::valor('NOMBRE_SISTEMA', config('app.name', 'Control de Asistencia Municipal'));
        $fileName = 'reporte_asistencia_resumen_' . $desde . '_a_' . $hasta . ($departamentoId ? ('_dep_' . $departamentoId) : '') . '.xls';

        return response()
            ->view('PgAsistencias.export_xls_resumen', compact('desde', 'hasta', 'departamentoId', 'departamentos', 'summary', 'resumenDept', 'logoUrl', 'nombreSistema'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function ExportXlsDetalle(Request $request)
    {
        [$desde, $hasta, $departamentoId, $departamentos, $personas] = $this->resolveFilters($request);
        $detalle = $this->buildDetalleAll($personas, $desde, $hasta, $departamentos);

        $logoUrl = PgConfiguracion::reportLogoUrl();
        $nombreSistema = (string) PgConfiguracion::valor('NOMBRE_SISTEMA', config('app.name', 'Control de Asistencia Municipal'));
        $fileName = 'reporte_asistencia_detalle_' . $desde . '_a_' . $hasta . ($departamentoId ? ('_dep_' . $departamentoId) : '') . '.xls';

        return response()
            ->view('PgAsistencias.export_xls_detalle', compact('desde', 'hasta', 'departamentoId', 'departamentos', 'detalle', 'logoUrl', 'nombreSistema'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function ExportPdfResumen(Request $request)
    {
        [$desde, $hasta, $departamentoId, $departamentos, $personas] = $this->resolveFilters($request);
        $summary = $this->buildSummary($personas, $desde, $hasta);
        $resumenDept = $this->buildResumenDepartamento($summary, $departamentos);

        $payload = [
            'tipo' => 'resumen',
            'desde' => $desde,
            'hasta' => $hasta,
            'departamentoId' => $departamentoId,
            'resumenDept' => $resumenDept,
        ];

        return view('PgAsistencias.export_pdf', [
            'payload' => $payload,
            'logoDataUri' => PgConfiguracion::reportLogoDataUri(),
            'nombreSistema' => (string) PgConfiguracion::valor('NOMBRE_SISTEMA', 'Control de Asistencia Municipal'),
        ]);
    }

    public function ExportPdfDetalle(Request $request)
    {
        [$desde, $hasta, $departamentoId, $departamentos, $personas] = $this->resolveFilters($request);
        $detalle = $this->buildDetalleAll($personas, $desde, $hasta, $departamentos);

        $payload = [
            'tipo' => 'detalle',
            'desde' => $desde,
            'hasta' => $hasta,
            'departamentoId' => $departamentoId,
            'detalle' => $detalle,
        ];

        return view('PgAsistencias.export_pdf', [
            'payload' => $payload,
            'logoDataUri' => PgConfiguracion::reportLogoDataUri(),
            'nombreSistema' => (string) PgConfiguracion::valor('NOMBRE_SISTEMA', 'Control de Asistencia Municipal'),
        ]);
    }

    // ==========================================================
    // NUEVO: Reporte matriz por Día y Evento (PDF/XLS + pantalla)
    // ==========================================================

    public function ReporteDiaEvento(Request $request)
    {
        [$desde, $hasta, $departamentoId, $personaId, $departamentos, $personasSelect, $personas] = $this->resolveFiltersDiaEvento($request);

        $data = $this->buildDiaEvento($personas, $desde, $hasta);

        return view('PgAsistencias.reporte_dia_evento', [
            'desde' => $desde,
            'hasta' => $hasta,
            'departamentoId' => $departamentoId,
            'personaId' => $personaId,
            'departamentos' => $departamentos,
            'personasSelect' => $personasSelect,
            'dates' => $data['dates'],
            'rows' => $data['rows'],
        ]);
    }

    public function ExportXlsDiaEvento(Request $request)
    {
        [$desde, $hasta, $departamentoId, $personaId, $departamentos, $personasSelect, $personas] = $this->resolveFiltersDiaEvento($request);
        $data = $this->buildDiaEvento($personas, $desde, $hasta);

        return response()->view('PgAsistencias.export_xls_dia_evento', [
            'desde' => $desde,
            'hasta' => $hasta,
            'departamentoId' => $departamentoId,
            'personaId' => $personaId,
            'dates' => $data['dates'],
            'rows' => $data['rows'],
        ])->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="reporte_asistencia_dia_evento_' . $desde . '_a_' . $hasta . '.xls"');
    }

    public function ExportPdfDiaEvento(Request $request)
    {
        [$desde, $hasta, $departamentoId, $personaId, $departamentos, $personasSelect, $personas] = $this->resolveFiltersDiaEvento($request);
        $data = $this->buildDiaEvento($personas, $desde, $hasta);

        return view('PgAsistencias.export_pdf_dia_evento', [
            'desde' => $desde,
            'hasta' => $hasta,
            'departamentoId' => $departamentoId,
            'personaId' => $personaId,
            'dates' => $data['dates'],
            'rows' => $data['rows'],
            'logoDataUri' => PgConfiguracion::reportLogoDataUri(),
            'nombreSistema' => (string) PgConfiguracion::valor('NOMBRE_SISTEMA', 'Control de Asistencia Municipal'),
        ]);
    }

    // ==========================================================
    // NUEVO: Reporte por Mes (calendario por semanas)
    // ==========================================================

    public function ReporteMes(Request $request)
    {
        [$anio, $mes, $todosMeses, $departamentoId, $personaId, $departamentos, $personasSelect, $personas] = $this->resolveFiltersMes($request);

        $data = $this->buildMesCalendario($personas, $anio, $mes, $todosMeses);

        return view('PgAsistencias.reporte_mes', [
            'anio' => $anio,
            'mes' => $mes,
            'todosMeses' => $todosMeses,
            'departamentoId' => $departamentoId,
            'personaId' => $personaId,
            'departamentos' => $departamentos,
            'personasSelect' => $personasSelect,
            'months' => $data['months'],
        ]);
    }

    public function ExportXlsMes(Request $request)
    {
        [$anio, $mes, $todosMeses, $departamentoId, $personaId, $departamentos, $personasSelect, $personas] = $this->resolveFiltersMes($request);
        $data = $this->buildMesCalendario($personas, $anio, $mes, $todosMeses);

        $fileName = 'reporte_asistencia_mes_' . $anio;
        if (!$todosMeses && $mes) {
            $fileName .= '_' . sprintf('%02d', $mes);
        }
        $fileName .= '.xls';

        return response()->view('PgAsistencias.export_xls_mes', [
            'anio' => $anio,
            'mes' => $mes,
            'todosMeses' => $todosMeses,
            'departamentoId' => $departamentoId,
            'personaId' => $personaId,
            'months' => $data['months'],
        ])->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function ExportPdfMes(Request $request)
    {
        [$anio, $mes, $todosMeses, $departamentoId, $personaId, $departamentos, $personasSelect, $personas] = $this->resolveFiltersMes($request);
        $data = $this->buildMesCalendario($personas, $anio, $mes, $todosMeses);

        return view('PgAsistencias.export_pdf_mes', [
            'anio' => $anio,
            'mes' => $mes,
            'todosMeses' => $todosMeses,
            'departamentoId' => $departamentoId,
            'personaId' => $personaId,
            'months' => $data['months'],
            'logoDataUri' => PgConfiguracion::reportLogoDataUri(),
            'nombreSistema' => (string) PgConfiguracion::valor('NOMBRE_SISTEMA', 'Control de Asistencia Municipal'),
        ]);
    }

    // ---------------- Helpers ----------------

    private function resolveFiltersDiaEvento(Request $request): array
    {
        $desde = $this->parseDateInput($request->input('desde'), Carbon::today()->startOfMonth()->format('Y-m-d'));
        $hasta = $this->parseDateInput($request->input('hasta'), Carbon::today()->format('Y-m-d'));

        $departamentoId = trim((string) $request->input('departamento_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }
        $personaId = trim((string) $request->input('persona_id'));
        if ($personaId === '') {
            $personaId = null;
        }

        $departamentos = $this->departamentosVigentes();

        // Combo de personas (para filtro por persona)
        $personasSelectQ = PgPersona::query()->with('departamento')->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) {
            $personasSelectQ->where('departamento_id', $departamentoId);
        }
        $personasSelect = $personasSelectQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        // Personas que entran al reporte
        $personasQ = PgPersona::query()->with('departamento')->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) {
            $personasQ->where('departamento_id', $departamentoId);
        }
        if ($personaId) {
            $personasQ->where('id', $personaId);
        }
        $personas = $personasQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        return [$desde, $hasta, $departamentoId, $personaId, $departamentos, $personasSelect, $personas];
    }

    private function resolveFiltersMes(Request $request): array
    {
        $today = Carbon::today();
        $anio = (int)($request->input('anio') ?? $today->year);
        if ($anio < 2000) $anio = $today->year;

        $mesRaw = trim((string)$request->input('mes'));
        $mes = null;
        if ($mesRaw !== '') {
            // soporta "YYYY-MM" o "MM"
            if (preg_match('/^(\d{4})-(\d{1,2})$/', $mesRaw, $m)) {
                $anio = (int)$m[1];
                $mes = (int)$m[2];
            } else {
                $mes = (int)$mesRaw;
            }
            if ($mes < 1 || $mes > 12) $mes = null;
        }

        // Checkbox: si NO viene en el request, es false (cuando el usuario lo desmarca)
        $todosMeses = $request->has('todos_meses') && ((string)$request->input('todos_meses')) === '1';
        // Si no hay mes seleccionado, forzamos todos los meses
        if ($mes === null) $todosMeses = true;

        $departamentoId = trim((string)$request->input('departamento_id'));
        if ($departamentoId === '') $departamentoId = null;
        $personaId = trim((string)$request->input('persona_id'));
        if ($personaId === '') $personaId = null;

        $departamentos = $this->departamentosVigentes();

        $personasSelectQ = PgPersona::query()->with('departamento')->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) $personasSelectQ->where('departamento_id', $departamentoId);
        $personasSelect = $personasSelectQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        $personasQ = PgPersona::query()->with('departamento')->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) $personasQ->where('departamento_id', $departamentoId);
        if ($personaId) $personasQ->where('id', $personaId);
        $personas = $personasQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        return [$anio, $mes, $todosMeses, $departamentoId, $personaId, $departamentos, $personasSelect, $personas];
    }

    private function buildMesCalendario($personas, int $anio, ?int $mes, bool $todosMeses): array
    {
        $today = Carbon::today();
        $maxMonth = ($anio === $today->year) ? $today->month : 12;
        $months = [];

        if (!$todosMeses && $mes) {
            $monthList = [$mes];
        } else {
            $monthList = range(1, $maxMonth);
        }

        // Rango global para queries
        $from = Carbon::create($anio, min($monthList), 1)->startOfMonth();
        $to = Carbon::create($anio, max($monthList), 1)->endOfMonth();
        if ($anio === $today->year && max($monthList) === $today->month) {
            $to = $today->copy();
        }

        $personIds = $personas->pluck('id')->all();
        $deptIds = $personas->pluck('departamento_id')->filter()->unique()->values()->all();

        // Asistencias/Justificaciones (lookup rápido)
        $asistencias = PgAsistenciaEvento::query()
            ->whereBetween('fecha', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->whereIn('persona_id', $personIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id', 'fecha', 'estado_asistencia']);

        $asistMap = [];
        foreach ($asistencias as $a) {
            foreach ($this->expandAsistenciaEntries($a) as $entry) {
                $k = $entry['evento_id'] . '|' . $entry['fecha'];
                $asistMap[$entry['persona_id']][$k] = $entry['estado_asistencia'];
            }
        }

        $justs = PgJustificacionAsistencia::query()
            ->whereBetween('fecha', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->whereIn('persona_id', $personIds)
            ->where('estado_revision', 'A')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id', 'fecha']);

        $justMap = [];
        foreach ($justs as $j) {
            $k = $j->evento_id . '|' . $j->fecha->format('Y-m-d');
            $justMap[$j->persona_id][$k] = true;
        }

        foreach ($monthList as $m) {
            $mStart = Carbon::create($anio, $m, 1)->startOfMonth();
            $mEnd = Carbon::create($anio, $m, 1)->endOfMonth();
            if ($anio === $today->year && $m === $today->month) {
                $mEnd = $today->copy();
            }

            // preparar calendario por semanas (L-D)
            $weeks = [];
            $cursor = $mStart->copy()->startOfWeek(Carbon::MONDAY);
            $endCursor = $mEnd->copy()->endOfWeek(Carbon::SUNDAY);

            while ($cursor->lte($endCursor)) {
                $days = [];
                for ($i = 0; $i < 7; $i++) {
                    $d = $cursor->copy()->addDays($i);
                    if ($d->month !== $m || $d->lt($mStart) || $d->gt($mEnd)) {
                        $days[] = null;
                    } else {
                        $days[] = $d->format('Y-m-d');
                    }
                }
                $weeks[] = $days;
                $cursor->addWeek();
            }

            // pre-cargar eventos/targets por fecha del mes (solo las que aparecen en el calendario)
            $dateEvents = [];  // date => [eventId => PgEvento]
            $dateTargets = []; // date => targets
            $allDates = collect($weeks)->flatten()->filter()->unique()->values()->all();
            foreach ($allDates as $dateStr) {
                $events = $this->eventsForDate($dateStr);
                if (empty($events)) {
                    $dateEvents[$dateStr] = [];
                    $dateTargets[$dateStr] = ['deps' => [], 'pers' => []];
                    continue;
                }

                $targets = $this->targetsForDate($dateStr, array_keys($events));
                $filtered = [];
                foreach ($events as $eid => $e) {
                    $hasDep = !empty($targets['deps'][$eid] ?? []);
                    $hasPer = !empty($targets['pers'][$eid] ?? []);
                    if (!$hasDep && !$hasPer) {
                        $filtered[$eid] = $e;
                        continue;
                    }
                    if ($hasDep && !empty(array_intersect($deptIds, $targets['deps'][$eid] ?? []))) {
                        $filtered[$eid] = $e;
                        continue;
                    }
                    if ($hasPer && !empty(array_intersect($personIds, $targets['pers'][$eid] ?? []))) {
                        $filtered[$eid] = $e;
                        continue;
                    }
                }
                $dateEvents[$dateStr] = $filtered;
                $dateTargets[$dateStr] = $targets;
            }

            // filas por persona
            $rows = [];
            foreach ($personas as $p) {
                $row = [
                    'persona_id' => $p->id,
                    'nombre' => $p->nombre_completo,
                    'departamento' => optional($p->departamento)->descripcion,
                    'cells' => [],
                    'totales' => ['convocados' => 0, 'asistio' => 0, 'justifico' => 0, 'no' => 0],
                ];

                foreach ($allDates as $dateStr) {
                    $events = $dateEvents[$dateStr] ?? [];
                    $targets = $dateTargets[$dateStr] ?? ['deps' => [], 'pers' => []];

                    $cntA = 0; $cntJ = 0; $cntF = 0;
                    foreach ($events as $eid => $e) {
                        if (!$this->eventAppliesToPerson($eid, $p->id, $p->departamento_id, $targets)) {
                            continue;
                        }
                        $k = $eid . '|' . $dateStr;
                        $st = $asistMap[$p->id][$k] ?? '';
                        if ($st === 'A') {
                            $cntA++;
                        } elseif (!empty($justMap[$p->id][$k])) {
                            $cntJ++;
                        } elseif ($st === 'F') {
                            $cntF++;
                        } else {
                            // fallback: si no hay registro aún, cuenta como falta
                            $cntF++;
                        }
                    }

                    if (($cntA + $cntJ + $cntF) === 0) {
                        $row['cells'][$dateStr] = null;
                        continue;
                    }

                    // Estado resumido para la celda: A / J / F
                    $mark = 'A';
                    if ($cntF > 0) {
                        $mark = 'F';
                    } elseif ($cntA === 0 && $cntJ > 0) {
                        $mark = 'J';
                    }

                    $row['cells'][$dateStr] = [
                        'mark' => $mark,
                        'a' => $cntA,
                        'j' => $cntJ,
                        'f' => $cntF,
                    ];
                    $row['totales']['convocados'] += ($cntA + $cntJ + $cntF);
                    $row['totales']['asistio'] += $cntA;
                    $row['totales']['justifico'] += $cntJ;
                    $row['totales']['no'] += $cntF;
                }

                $rows[] = $row;
            }

            $months[] = [
                'anio' => $anio,
                'mes' => $m,
                'titulo' => $mStart->translatedFormat('F') . ' ' . $anio,
                'weeks' => $weeks,
                'rows' => $rows,
            ];
        }

        return ['months' => $months];
    }

    private function dayLabel(Carbon $d): string
    {
        // Carbon: 0=Domingo, 1=Lunes ... 6=Sábado
        $map = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
        $abbr = $map[$d->dayOfWeek] ?? 'D';
        return $abbr . '(' . $d->day . ')';
    }

    private function buildDiaEvento($personas, string $desde, string $hasta): array
    {
        $personIds = $personas->pluck('id')->all();
        $deptIds = $personas->pluck('departamento_id')->filter()->unique()->values()->all();

        // Mapa de asistencias y justificaciones aprobadas (rápido lookup)
        $asistencias = PgAsistenciaEvento::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereIn('persona_id', $personIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id', 'fecha', 'estado_asistencia']);

        $asistMap = [];
        foreach ($asistencias as $a) {
            foreach ($this->expandAsistenciaEntries($a) as $entry) {
                $k = $entry['evento_id'] . '|' . $entry['fecha'];
                $asistMap[$entry['persona_id']][$k] = $entry['estado_asistencia'];
            }
        }

        $justs = PgJustificacionAsistencia::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereIn('persona_id', $personIds)
            ->where('estado_revision', 'A')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id', 'fecha']);

        $justMap = [];
        foreach ($justs as $j) {
            $k = $j->evento_id . '|' . $j->fecha->format('Y-m-d');
            $justMap[$j->persona_id][$k] = true;
        }

        // Fechas (solo días con eventos en el rango, filtrados por relevancia)
        $period = CarbonPeriod::create($desde, $hasta);
        $dates = [];
        $dateEvents = [];  // dateStr => [eventId => PgEvento]
        $dateTargets = []; // dateStr => ['deps'=>[], 'pers'=>[]]

        foreach ($period as $d) {
            $dateStr = $d->format('Y-m-d');
            $events = $this->eventsForDate($dateStr);
            if (empty($events)) {
                continue;
            }

            $targets = $this->targetsForDate($dateStr, array_keys($events));
            $filtered = [];
            foreach ($events as $eid => $e) {
                $hasDep = !empty($targets['deps'][$eid] ?? []);
                $hasPer = !empty($targets['pers'][$eid] ?? []);

                // Sin targets => aplica a todos (relevante)
                if (!$hasDep && !$hasPer) {
                    $filtered[$eid] = $e;
                    continue;
                }
                // Aplica a alguno de los departamentos o personas del filtro
                if ($hasDep && !empty(array_intersect($deptIds, $targets['deps'][$eid] ?? []))) {
                    $filtered[$eid] = $e;
                    continue;
                }
                if ($hasPer && !empty(array_intersect($personIds, $targets['pers'][$eid] ?? []))) {
                    $filtered[$eid] = $e;
                    continue;
                }
            }

            if (empty($filtered)) {
                continue;
            }

            $dates[] = [
                'date' => $dateStr,
                'label' => $this->dayLabel(Carbon::parse($dateStr)),
            ];
            $dateEvents[$dateStr] = $filtered;
            $dateTargets[$dateStr] = $targets;
        }

        // Filas
        $rows = [];
        foreach ($personas as $p) {
            $row = [
                'persona_id' => $p->id,
                'nombre' => $p->nombre_completo,
                'departamento' => optional($p->departamento)->descripcion,
                'cells' => [],
                'totales' => ['convocados' => 0, 'asistio' => 0, 'justifico' => 0, 'no' => 0],
            ];

            foreach ($dates as $d) {
                $dateStr = $d['date'];
                $events = $dateEvents[$dateStr] ?? [];
                $targets = $dateTargets[$dateStr] ?? ['deps' => [], 'pers' => []];

                $lines = [];
                $cntA = 0;
                $cntJ = 0;
                $cntF = 0;

                foreach ($events as $eid => $e) {
                    if (!$this->eventAppliesToPerson($eid, $p->id, $p->departamento_id, $targets)) {
                        continue;
                    }
                    $k = $eid . '|' . $dateStr;
                    $status = 'F';
                    $st = $asistMap[$p->id][$k] ?? '';
                    if ($st === 'A') {
                        $status = 'A';
                        $cntA++;
                    } elseif (!empty($justMap[$p->id][$k])) {
                        $status = 'J';
                        $cntJ++;
                    } elseif ($st === 'F') {
                        $status = 'F';
                        $cntF++;
                    } else {
                        $status = 'F';
                        $cntF++;
                    }

                    $titulo = trim((string) ($e->titulo ?? 'Evento'));
                    if ($titulo === '') $titulo = 'Evento';

                    $lines[] = [
                        's' => $status,
                        't' => $titulo,
                    ];
                }

                if (($cntA + $cntJ + $cntF) > 0) {
                    $row['cells'][$dateStr] = [
                        'a' => $cntA,
                        'j' => $cntJ,
                        'f' => $cntF,
                        'lines' => $lines,
                    ];
                    $row['totales']['convocados'] += ($cntA + $cntJ + $cntF);
                    $row['totales']['asistio'] += $cntA;
                    $row['totales']['justifico'] += $cntJ;
                    $row['totales']['no'] += $cntF;
                } else {
                    $row['cells'][$dateStr] = null;
                }
            }

            $rows[] = $row;
        }

        return ['dates' => $dates, 'rows' => $rows];
    }

    private function buildSummary($personas, string $desde, string $hasta): array
    {
        $personIds = $personas->pluck('id')->all();

        // Asistencias registradas
        $asistencias = PgAsistenciaEvento::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereIn('persona_id', $personIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id', 'fecha', 'estado_asistencia']);

        $asistMap = [];
        foreach ($asistencias as $a) {
            foreach ($this->expandAsistenciaEntries($a) as $entry) {
                $k = $entry['evento_id'] . '|' . $entry['fecha'];
                $asistMap[$entry['persona_id']][$k] = $entry['estado_asistencia'];
            }
        }

        // Justificaciones aprobadas
        $justs = PgJustificacionAsistencia::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereIn('persona_id', $personIds)
            ->where('estado_revision', 'A')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id', 'fecha']);

        $justMap = [];
        foreach ($justs as $j) {
            $k = $j->evento_id . '|' . $j->fecha->format('Y-m-d');
            $justMap[$j->persona_id][$k] = true;
        }

        $period = CarbonPeriod::create($desde, $hasta);

        $summary = [];

        foreach ($personas as $p) {
            $convocados = 0;
            $asistidos = 0;
            $justificados = 0;

            foreach ($period as $d) {
                $dateStr = $d->format('Y-m-d');
                $events = $this->eventsForDate($dateStr);
                if (empty($events)) {
                    continue;
                }
                $targets = $this->targetsForDate($dateStr, array_keys($events));

                foreach ($events as $eid => $e) {
                    if (!$this->eventAppliesToPerson($eid, $p->id, $p->departamento_id, $targets)) {
                        continue;
                    }

                    $convocados++;
                    $k = $eid . '|' . $dateStr;
                    $st = $asistMap[$p->id][$k] ?? '';
                    if ($st === 'A') {
                        $asistidos++;
                    } elseif (!empty($justMap[$p->id][$k])) {
                        $justificados++;
                    } elseif ($st === 'F') {
                        // falta registrada
                    }
                }
            }

            // Faltas: si existen registros F, se contabiliza por el faltante igualmente.
            $noAsistio = max(0, $convocados - $asistidos - $justificados);

            $summary[] = [
                'persona_id' => $p->id,
                'nombre' => $p->nombre_completo,
                'departamento_id' => $p->departamento_id,
                'convocados' => $convocados,
                'asistidos' => $asistidos,
                'justificados' => $justificados,
                'no_asistio' => $noAsistio,
            ];
        }

        // Ordenar por mayor no-asistio
        usort($summary, function ($a, $b) {
            return ($b['no_asistio'] <=> $a['no_asistio']) ?: strcmp($a['nombre'], $b['nombre']);
        });

        return $summary;
    }

    private function buildResumenDepartamento(array $summaryPersona, $departamentos): array
    {
        $depName = [];
        foreach ($departamentos as $d) {
            $depName[(string) $d->id] = (string) $d->descripcion;
        }

        $groups = [];
        foreach ($summaryPersona as $r) {
            $depId = (string) ($r['departamento_id'] ?? '');
            $key = $depId !== '' ? $depId : '__SIN__';
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'departamento_id' => $depId !== '' ? $depId : null,
                    'departamento' => $depId !== '' ? ($depName[$depId] ?? $depId) : 'Sin departamento',
                    'totales' => ['convocados' => 0, 'asistidos' => 0, 'justificados' => 0, 'no_asistio' => 0],
                    'personas' => [],
                ];
            }
            $groups[$key]['personas'][] = $r;
            $groups[$key]['totales']['convocados'] += (int) ($r['convocados'] ?? 0);
            $groups[$key]['totales']['asistidos'] += (int) ($r['asistidos'] ?? 0);
            $groups[$key]['totales']['justificados'] += (int) ($r['justificados'] ?? 0);
            $groups[$key]['totales']['no_asistio'] += (int) ($r['no_asistio'] ?? 0);
        }

        // ordenar por nombre departamento
        uasort($groups, function ($a, $b) {
            return strcmp($a['departamento'], $b['departamento']);
        });

        return array_values($groups);
    }

    private function buildDetalleAll($personas, string $desde, string $hasta, $departamentos): array
    {
        $depName = [];
        foreach ($departamentos as $d) {
            $depName[(string) $d->id] = (string) $d->descripcion;
        }

        $rows = [];
        foreach ($personas as $p) {
            $det = $this->buildDetallePersona($p, $desde, $hasta);
            $depId = (string) ($p->departamento_id ?? '');
            $depDesc = $depId !== '' ? ($depName[$depId] ?? $depId) : 'Sin departamento';

            foreach ($det as $r) {
                $rows[] = [
                    'departamento_id' => $depId !== '' ? $depId : null,
                    'departamento' => $depDesc,
                    'persona_id' => $p->id,
                    'persona' => $p->nombre_completo,
                    'fecha' => $r['fecha'],
                    'evento' => $r['evento'],
                    'estado' => $r['estado'],
                ];
            }
        }

        usort($rows, function ($a, $b) {
            $c = strcmp($a['departamento'], $b['departamento']);
            if ($c !== 0) return $c;
            $c = strcmp($a['persona'], $b['persona']);
            if ($c !== 0) return $c;
            // fecha dd/mm/yyyy -> yyyymmdd
            $fa = $this->dateKey($a['fecha']);
            $fb = $this->dateKey($b['fecha']);
            $c = strcmp($fa, $fb);
            if ($c !== 0) return $c;
            return strcmp($a['evento'], $b['evento']);
        });

        return $rows;
    }

    private function buildDetallePersona(PgPersona $persona, string $desde, string $hasta): array
    {
        $period = CarbonPeriod::create($desde, $hasta);

        $asistencias = PgAsistenciaEvento::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->where('persona_id', $persona->id)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['persona_id', 'evento_id', 'fecha', 'estado_asistencia']);
        $asistSet = [];
        foreach ($asistencias as $a) {
            foreach ($this->expandAsistenciaEntries($a) as $entry) {
                if ($entry['estado_asistencia'] === 'A') {
                    $asistSet[$entry['evento_id'] . '|' . $entry['fecha']] = true;
                }
            }
        }

        $justs = PgJustificacionAsistencia::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->where('persona_id', $persona->id)
            ->where('estado_revision', 'A')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get(['evento_id', 'fecha']);
        $justSet = [];
        foreach ($justs as $j) {
            $justSet[$j->evento_id . '|' . $j->fecha->format('Y-m-d')] = true;
        }

        $rows = [];
        foreach ($period as $d) {
            $dateStr = $d->format('Y-m-d');
            $events = $this->eventsForDate($dateStr);
            if (empty($events)) continue;
            $targets = $this->targetsForDate($dateStr, array_keys($events));

            foreach ($events as $eid => $e) {
                if (!$this->eventAppliesToPerson($eid, $persona->id, $persona->departamento_id, $targets)) {
                    continue;
                }

                $k = $eid . '|' . $dateStr;
                $estado = 'NO ASISTIÓ';
                if (!empty($asistSet[$k])) {
                    $estado = 'ASISTIÓ';
                } elseif (!empty($justSet[$k])) {
                    $estado = 'JUSTIFICÓ';
                }

                $rows[] = [
                    'fecha' => $d->format('d/m/Y'),
                    'evento_id' => $eid,
                    'evento' => $e->titulo,
                    'estado' => $estado,
                ];
            }
        }

        return $rows;
    }

    /**
     * Cache simple por request (evita re-consultar en loops)
     */
    private array $eventsCache = [];

    private array $targetsCache = [];

    private function targetsForDate(string $date, array $eventIds): array
    {
        if (isset($this->targetsCache[$date])) {
            return $this->targetsCache[$date];
        }
        return $this->targetsCache[$date] = $this->loadEventTargets($eventIds);
    }

    private function resolveFilters(Request $request): array
    {
        $desde = $this->parseDateInput($request->input('desde'), Carbon::today()->startOfMonth()->format('Y-m-d'));
        $hasta = $this->parseDateInput($request->input('hasta'), Carbon::today()->format('Y-m-d'));
        $departamentoId = trim((string) $request->input('departamento_id'));
        $personaId = trim((string) $request->input('persona_id'));
        if ($departamentoId === '') {
            $departamentoId = null;
        }
        if ($personaId === '') {
            $personaId = null;
        }

        $departamentos = $this->departamentosVigentes();

        $personasQ = PgPersona::query()->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        });
        if ($departamentoId) {
            $personasQ->where('departamento_id', $departamentoId);
        }
        if ($personaId) {
            $personasQ->where('id', $personaId);
        }
        $personas = $personasQ->orderBy('apellido1')->orderBy('apellido2')->orderBy('nombres')->get();

        return [$desde, $hasta, $departamentoId, $departamentos, $personas];
    }

    private function dateKey(string $fechaDdMmYyyy): string
    {
        $parts = explode('/', $fechaDdMmYyyy);
        if (count($parts) === 3) {
            return $parts[2] . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        }
        return $fechaDdMmYyyy;
    }

    private function eventsForDate(string $date): array
    {
        if (isset($this->eventsCache[$date])) {
            return $this->eventsCache[$date];
        }

        $events = PgEvento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->whereDate('fecha_inicio', '<=', $date)
            ->whereDate('fecha_fin', '>=', $date)
            ->get();

        $map = [];
        foreach ($events as $e) {
            $map[$e->id] = $e;
        }

        return $this->eventsCache[$date] = $map;
    }

    private function loadEventTargets(array $eventIds): array
    {
        $eventIds = array_values(array_unique(array_filter($eventIds)));
        if (empty($eventIds)) {
            return ['deps' => [], 'pers' => []];
        }

        $deps = DB::table('pg_evento_departamento')
            ->whereIn('evento_id', $eventIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();

        $pers = DB::table('pg_evento_persona')
            ->whereIn('evento_id', $eventIds)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->get();

        $depMap = [];
        foreach ($deps as $r) {
            $depMap[$r->evento_id][] = (string) $r->departamento_id;
        }
        $perMap = [];
        foreach ($pers as $r) {
            $perMap[$r->evento_id][] = (string) $r->persona_id;
        }

        foreach ($depMap as $eid => $arr) {
            $depMap[$eid] = array_values(array_unique($arr));
        }
        foreach ($perMap as $eid => $arr) {
            $perMap[$eid] = array_values(array_unique($arr));
        }

        return ['deps' => $depMap, 'pers' => $perMap];
    }

    private function eventAppliesToPerson(string $eventoId, string $personaId, ?string $departamentoId, array $targets): bool
    {
        $deps = $targets['deps'][$eventoId] ?? [];
        $pers = $targets['pers'][$eventoId] ?? [];

        if (empty($deps) && empty($pers)) {
            return true;
        }

        if (!empty($departamentoId) && in_array($departamentoId, $deps, true)) {
            return true;
        }
        if (in_array($personaId, $pers, true)) {
            return true;
        }

        return false;
    }

    private function decodeListValue($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        if ($value === null) {
            return [];
        }

        $s = trim((string) $value);
        if ($s === '') {
            return [];
        }
        if (str_starts_with($s, '[') && str_ends_with($s, ']')) {
            $decoded = json_decode($s, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values($decoded);
            }
        }

        return [$s];
    }

    private function expandAsistenciaEntries(PgAsistenciaEvento $row): array
    {
        $eventIds = $this->decodeListValue($row->evento_id);
        $states = $this->decodeListValue($row->estado_asistencia);

        if (empty($eventIds)) {
            return [];
        }

        $fecha = $row->fecha instanceof Carbon ? $row->fecha->format('Y-m-d') : Carbon::parse($row->fecha)->format('Y-m-d');
        $personaId = (string) ($row->persona_id ?? '');
        $entries = [];

        foreach ($eventIds as $idx => $eventId) {
            $eid = trim((string) $eventId);
            if ($eid === '') {
                continue;
            }
            $state = trim((string) ($states[$idx] ?? ($states[0] ?? '')));
            $entries[] = [
                'persona_id' => $personaId,
                'evento_id' => $eid,
                'fecha' => $fecha,
                'estado_asistencia' => $state,
            ];
        }

        return $entries;
    }
}
