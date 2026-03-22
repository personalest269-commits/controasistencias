<?php

use App\Http\Controllers\PgAsistenciasController;
use App\Http\Controllers\PgAsistenciaReportesController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'auth', 'permission:pg_asistencias']], function () {
    Route::get('/PgAsistencias', [PgAsistenciasController::class, 'Index'])->name('PgAsistenciasIndex');
    Route::post('/PgAsistencias/actualizar', [PgAsistenciasController::class, 'Actualizar'])->name('PgAsistenciasActualizar');
    Route::post('/PgAsistencias/cerrar-dia', [PgAsistenciasController::class, 'CerrarDia'])->name('PgAsistenciasCerrarDia');

    // Helpers UI (select2/ajax)
    Route::get('/PgAsistencias/personas-search', [PgAsistenciasController::class, 'PersonasSearch'])->name('PgAsistenciasPersonasSearch');
    Route::post('/PgAsistencias/actualizar-item', [PgAsistenciasController::class, 'ActualizarItem'])->name('PgAsistenciasActualizarItem');
});

Route::group(['middleware' => ['web', 'auth', 'permission:pg_reporte_asistencias']], function () {
    Route::get('/PgAsistencias/reportes', [PgAsistenciaReportesController::class, 'Index'])->name('PgAsistenciasReportes');
    Route::get('/PgAsistencias/reportes/persona/{personaId}', [PgAsistenciaReportesController::class, 'DetallePersona'])->name('PgAsistenciasReportePersona');
    Route::get('/PgAsistencias/reportes/export', [PgAsistenciaReportesController::class, 'Export'])->name('PgAsistenciasReporteExport');

    // Exportaciones nuevas (resumen por departamento y detallado por día)
    Route::get('/PgAsistencias/reportes/export/xls/resumen', [PgAsistenciaReportesController::class, 'ExportXlsResumen'])->name('PgAsistenciasReporteExportXlsResumen');
    Route::get('/PgAsistencias/reportes/export/xls/detalle', [PgAsistenciaReportesController::class, 'ExportXlsDetalle'])->name('PgAsistenciasReporteExportXlsDetalle');
    Route::get('/PgAsistencias/reportes/export/pdf/resumen', [PgAsistenciaReportesController::class, 'ExportPdfResumen'])->name('PgAsistenciasReporteExportPdfResumen');
    Route::get('/PgAsistencias/reportes/export/pdf/detalle', [PgAsistenciaReportesController::class, 'ExportPdfDetalle'])->name('PgAsistenciasReporteExportPdfDetalle');

    // NUEVO: Asistencia por Día y Evento (rango + export PDF/XLS)
    Route::get('/PgAsistencias/reportes/dia-evento', [PgAsistenciaReportesController::class, 'ReporteDiaEvento'])->name('PgAsistenciasReporteDiaEvento');
    Route::get('/PgAsistencias/reportes/dia-evento/export/xls', [PgAsistenciaReportesController::class, 'ExportXlsDiaEvento'])->name('PgAsistenciasReporteDiaEventoXls');
    Route::get('/PgAsistencias/reportes/dia-evento/export/pdf', [PgAsistenciaReportesController::class, 'ExportPdfDiaEvento'])->name('PgAsistenciasReporteDiaEventoPdf');

    // NUEVO: Asistencia por Mes (calendario por semanas)
    Route::get('/PgAsistencias/reportes/mes', [PgAsistenciaReportesController::class, 'ReporteMes'])->name('PgAsistenciasReporteMes');
    Route::get('/PgAsistencias/reportes/mes/export/xls', [PgAsistenciaReportesController::class, 'ExportXlsMes'])->name('PgAsistenciasReporteMesXls');
    Route::get('/PgAsistencias/reportes/mes/export/pdf', [PgAsistenciaReportesController::class, 'ExportPdfMes'])->name('PgAsistenciasReporteMesPdf');
});
