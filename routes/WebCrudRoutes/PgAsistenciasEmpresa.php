<?php

use App\Http\Controllers\PgAsistenciasController;
use App\Http\Controllers\PgAsistenciaReportesEmpresaController;
use Illuminate\Support\Facades\Route;

// =============================================
// Asistencias masivas filtrando por EMPRESA
// =============================================

Route::group(['middleware' => ['web', 'auth', 'permission:pg_asistencias']], function () {
    Route::get('/PgAsistenciasEmpresa', [PgAsistenciasController::class, 'IndexEmpresa'])->name('PgAsistenciasEmpresaIndex');
    Route::post('/PgAsistenciasEmpresa/actualizar', [PgAsistenciasController::class, 'ActualizarEmpresa'])->name('PgAsistenciasEmpresaActualizar');
    Route::post('/PgAsistenciasEmpresa/cerrar-dia', [PgAsistenciasController::class, 'CerrarDiaEmpresa'])->name('PgAsistenciasEmpresaCerrarDia');

    // Helpers UI (select2/ajax)
    Route::get('/PgAsistenciasEmpresa/personas-search', [PgAsistenciasController::class, 'PersonasSearchEmpresa'])->name('PgAsistenciasEmpresaPersonasSearch');
    Route::post('/PgAsistenciasEmpresa/actualizar-item', [PgAsistenciasController::class, 'ActualizarItemEmpresa'])->name('PgAsistenciasEmpresaActualizarItem');
});

// =============================================
// Reportes de asistencia por EMPRESA
// =============================================

Route::group(['middleware' => ['web', 'auth', 'permission:pg_reporte_asistencias']], function () {
    Route::get('/PgAsistenciasEmpresa/reportes', [PgAsistenciaReportesEmpresaController::class, 'Index'])->name('PgAsistenciasEmpresaReportes');
    Route::get('/PgAsistenciasEmpresa/reportes/persona/{personaId}', [PgAsistenciaReportesEmpresaController::class, 'DetallePersona'])->name('PgAsistenciasEmpresaReportePersona');

    Route::get('/PgAsistenciasEmpresa/reportes/export/xls/resumen', [PgAsistenciaReportesEmpresaController::class, 'ExportXlsResumen'])->name('PgAsistenciasEmpresaReporteExportXlsResumen');
    Route::get('/PgAsistenciasEmpresa/reportes/export/xls/detalle', [PgAsistenciaReportesEmpresaController::class, 'ExportXlsDetalle'])->name('PgAsistenciasEmpresaReporteExportXlsDetalle');
    Route::get('/PgAsistenciasEmpresa/reportes/export/pdf/resumen', [PgAsistenciaReportesEmpresaController::class, 'ExportPdfResumen'])->name('PgAsistenciasEmpresaReporteExportPdfResumen');
    Route::get('/PgAsistenciasEmpresa/reportes/export/pdf/detalle', [PgAsistenciaReportesEmpresaController::class, 'ExportPdfDetalle'])->name('PgAsistenciasEmpresaReporteExportPdfDetalle');

    // Asistencia por Día y Evento (rango + export PDF/XLS)
    Route::get('/PgAsistenciasEmpresa/reportes/dia-evento', [PgAsistenciaReportesEmpresaController::class, 'ReporteDiaEvento'])->name('PgAsistenciasEmpresaReporteDiaEvento');
    Route::get('/PgAsistenciasEmpresa/reportes/dia-evento/export/xls', [PgAsistenciaReportesEmpresaController::class, 'ExportXlsDiaEvento'])->name('PgAsistenciasEmpresaReporteDiaEventoXls');
    Route::get('/PgAsistenciasEmpresa/reportes/dia-evento/export/pdf', [PgAsistenciaReportesEmpresaController::class, 'ExportPdfDiaEvento'])->name('PgAsistenciasEmpresaReporteDiaEventoPdf');

    // Asistencia por Mes
    Route::get('/PgAsistenciasEmpresa/reportes/mes', [PgAsistenciaReportesEmpresaController::class, 'ReporteMes'])->name('PgAsistenciasEmpresaReporteMes');
    Route::get('/PgAsistenciasEmpresa/reportes/mes/export/xls', [PgAsistenciaReportesEmpresaController::class, 'ExportXlsMes'])->name('PgAsistenciasEmpresaReporteMesXls');
    Route::get('/PgAsistenciasEmpresa/reportes/mes/export/pdf', [PgAsistenciaReportesEmpresaController::class, 'ExportPdfMes'])->name('PgAsistenciasEmpresaReporteMesPdf');
});
