<?php

use App\Http\Controllers\PgEventosEmpresaController;
use Illuminate\Support\Facades\Route;

// Reutilizamos el permiso de eventos para no romper roles actuales.
Route::group(['middleware' => ['web', 'auth', 'permission:pg_eventos']], function () {
    Route::get('/PgEventosEmpresa', [PgEventosEmpresaController::class, 'Index'])->name('PgEventosEmpresaIndex');

    Route::get('/PgEventosEmpresa/obtener/{id}', [PgEventosEmpresaController::class, 'Get'])->name('PgEventosEmpresaGet');
    Route::post('/PgEventosEmpresa/store', [PgEventosEmpresaController::class, 'Store'])->name('PgEventosEmpresaStore');
    Route::post('/PgEventosEmpresa/actualizar/{id}', [PgEventosEmpresaController::class, 'Update'])->name('PgEventosEmpresaUpdate');
    Route::post('/PgEventosEmpresa/eliminar/{id}', [PgEventosEmpresaController::class, 'Delete'])->name('PgEventosEmpresaDelete');
});
