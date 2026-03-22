<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PgTraduccionesController;

// Gestión de traducciones (DB)
Route::get('/PgTraducciones', [PgTraduccionesController::class, 'Index'])->name('PgTraduccionesIndex');
Route::post('/PgTraducciones/guardar', [PgTraduccionesController::class, 'Guardar'])->name('PgTraduccionesGuardar');
Route::post('/PgTraducciones/eliminar', [PgTraduccionesController::class, 'Eliminar'])->name('PgTraduccionesEliminar');
Route::post('/PgTraducciones/auto', [PgTraduccionesController::class, 'AutoTraducir'])->name('PgTraduccionesAuto');

// Cambiar idioma del sistema (sesión)
Route::get('/idioma/{codigo}', [PgTraduccionesController::class, 'CambiarIdioma'])->name('CambiarIdioma');
