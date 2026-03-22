<?php

use App\Http\Controllers\PgCierreAsistenciaCronController;
use Illuminate\Support\Facades\Route;

// =====================================================
// Cierre automático de asistencia (cron) + logs
// =====================================================

Route::group(['middleware' => ['web', 'auth', 'permission:pg_asistencias']], function () {
    Route::get('/PgCierreAsistenciaCron', [PgCierreAsistenciaCronController::class, 'Index'])->name('PgCierreAsistenciaCronIndex');
    Route::post('/PgCierreAsistenciaCron/ejecutar', [PgCierreAsistenciaCronController::class, 'Ejecutar'])->name('PgCierreAsistenciaCronEjecutar');
});
