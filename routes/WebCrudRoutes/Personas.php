<?php

Route::group(['middleware' => ['web', 'auth', 'XSS']], function () {
    Route::get('/Personas', ['uses' => 'PersonasController@Index', 'as' => 'PersonasIndex']);
    Route::post('/Personas/guardar', ['uses' => 'PersonasController@Store', 'as' => 'PersonasStore']);
    Route::get('/Personas/editar/{id}', ['uses' => 'PersonasController@Edit', 'as' => 'PersonasEdit']);
    Route::post('/Personas/actualizar/{id}', ['uses' => 'PersonasController@Update', 'as' => 'PersonasUpdate']);
    Route::post('/Personas/eliminar/{id}', ['uses' => 'PersonasController@Delete', 'as' => 'PersonasDelete']);
});
