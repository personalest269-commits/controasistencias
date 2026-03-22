<?php

use App\Http\Controllers\PgJustificacionesController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'auth', 'permission:pg_justificaciones']], function () {
    Route::get('/PgJustificaciones', [PgJustificacionesController::class, 'Index'])->name('PgJustificacionesIndex');

    // Select2 (bambox) options
    Route::get('/PgJustificaciones/options/departamentos', [PgJustificacionesController::class, 'OptionsDepartamentos'])
        ->name('PgJustificacionesOptionsDepartamentos');
    Route::get('/PgJustificaciones/options/personas', [PgJustificacionesController::class, 'OptionsPersonas'])
        ->name('PgJustificacionesOptionsPersonas');
    Route::get('/PgJustificaciones/options/eventos', [PgJustificacionesController::class, 'OptionsEventos'])
        ->name('PgJustificacionesOptionsEventos');

    // Validación en vivo (fecha/persona/evento)
    Route::post('/PgJustificaciones/validar', [PgJustificacionesController::class, 'Validar'])
        ->name('PgJustificacionesValidar');
    Route::post('/PgJustificaciones/store', [PgJustificacionesController::class, 'Store'])->name('PgJustificacionesStore');
    Route::get('/PgJustificaciones/obtener/{id}', [PgJustificacionesController::class, 'Get'])->name('PgJustificacionesGet');
    Route::post('/PgJustificaciones/actualizar/{id}', [PgJustificacionesController::class, 'Update'])->name('PgJustificacionesUpdate');
    // Aprobar/Rechazar: requiere permiso adicional asignable por rol
    Route::post('/PgJustificaciones/aprobar/{id}', [PgJustificacionesController::class, 'Aprobar'])
        ->middleware('permission:pg_justificaciones_aprobaciones')
        ->name('PgJustificacionesAprobar');
    Route::post('/PgJustificaciones/rechazar/{id}', [PgJustificacionesController::class, 'Rechazar'])
        ->middleware('permission:pg_justificaciones_aprobaciones')
        ->name('PgJustificacionesRechazar');
});
