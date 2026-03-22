<?php

use App\Http\Controllers\PgEmpresasController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'auth', 'permission:pg_empresa']], function () {
    Route::get('/PgEmpresas', [PgEmpresasController::class, 'Index'])->name('PgEmpresasIndex');
    Route::post('/PgEmpresas/store', [PgEmpresasController::class, 'Store'])->name('PgEmpresasStore');
    Route::get('/PgEmpresas/edit/{id}', [PgEmpresasController::class, 'Edit'])->name('PgEmpresasEdit');
    Route::post('/PgEmpresas/update/{id}', [PgEmpresasController::class, 'Update'])->name('PgEmpresasUpdate');
    Route::post('/PgEmpresas/delete/{id}', [PgEmpresasController::class, 'Delete'])->name('PgEmpresasDelete');

    Route::get('/PgEmpresas/eliminados', [PgEmpresasController::class, 'Eliminados'])->name('PgEmpresasEliminados');
    Route::post('/PgEmpresas/restaurar/{id}', [PgEmpresasController::class, 'Restore'])->name('PgEmpresasRestore');

    // Bambox (Select2)
    Route::get('/PgEmpresas/select2', [PgEmpresasController::class, 'Select2'])->name('PgEmpresasSelect2');
});
