<?php

use App\Http\Controllers\PgEventosController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'auth', 'permission:pg_eventos']], function () {
    Route::get('/PgEventos', [PgEventosController::class, 'Index'])->name('PgEventosIndex');
    Route::get('/PgEventos/feed', [PgEventosController::class, 'Feed'])->name('PgEventosFeed');
    Route::get('/PgEventos/upcoming', [PgEventosController::class, 'Upcoming'])->name('PgEventosUpcoming');
    Route::get('/PgEventos/obtener/{id}', [PgEventosController::class, 'Get'])->name('PgEventosGet');
    Route::post('/PgEventos/store', [PgEventosController::class, 'Store'])->name('PgEventosStore');
    Route::post('/PgEventos/actualizar/{id}', [PgEventosController::class, 'Update'])->name('PgEventosUpdate');
    Route::post('/PgEventos/eliminar/{id}', [PgEventosController::class, 'Delete'])->name('PgEventosDelete');

    Route::get('/PgEventos/eliminados', [PgEventosController::class, 'Eliminados'])->name('PgEventosEliminados');
    Route::post('/PgEventos/restaurar/{id}', [PgEventosController::class, 'Restore'])->name('PgEventosRestore');
});
