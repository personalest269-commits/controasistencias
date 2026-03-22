<?php

Route::group(['middleware' => ['web', 'auth', 'XSS', 'permission:pg_estado_civil']], function () {
    Route::get('/EstadoCivil', ['uses' => 'EstadoCivilController@Index', 'as' => 'EstadoCivilIndex']);
    Route::post('/EstadoCivil/guardar', ['uses' => 'EstadoCivilController@Store', 'as' => 'EstadoCivilStore']);
    Route::get('/EstadoCivil/editar/{id}', ['uses' => 'EstadoCivilController@Edit', 'as' => 'EstadoCivilEdit']);
    Route::post('/EstadoCivil/actualizar/{id}', ['uses' => 'EstadoCivilController@Update', 'as' => 'EstadoCivilUpdate']);
    Route::post('/EstadoCivil/eliminar/{id}', ['uses' => 'EstadoCivilController@Delete', 'as' => 'EstadoCivilDelete']);
});
