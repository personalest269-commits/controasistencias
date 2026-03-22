<?php

use App\Http\Controllers\PgDepartamentosController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'auth', 'permission:pg_departamento']], function () {
    Route::get('/PgDepartamentos', [PgDepartamentosController::class, 'Index'])->name('PgDepartamentosIndex');
    Route::post('/PgDepartamentos/store', [PgDepartamentosController::class, 'Store'])->name('PgDepartamentosStore');
    Route::get('/PgDepartamentos/edit/{id}', [PgDepartamentosController::class, 'Edit'])->name('PgDepartamentosEdit');
    Route::post('/PgDepartamentos/update/{id}', [PgDepartamentosController::class, 'Update'])->name('PgDepartamentosUpdate');
    Route::post('/PgDepartamentos/delete/{id}', [PgDepartamentosController::class, 'Delete'])->name('PgDepartamentosDelete');

    Route::get('/PgDepartamentos/eliminados', [PgDepartamentosController::class, 'Eliminados'])->name('PgDepartamentosEliminados');
    Route::post('/PgDepartamentos/restaurar/{id}', [PgDepartamentosController::class, 'Restore'])->name('PgDepartamentosRestore');
});
