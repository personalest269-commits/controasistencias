<?php

Route::group(['middleware' => ['web', 'auth', 'XSS', 'permission:pg_tipo_identificacion']], function () {
    Route::get('/TipoIdentificacion', ['uses' => 'TipoIdentificacionController@Index', 'as' => 'TipoIdentificacionIndex']);
    Route::post('/TipoIdentificacion/guardar', ['uses' => 'TipoIdentificacionController@Store', 'as' => 'TipoIdentificacionStore']);
    Route::get('/TipoIdentificacion/editar/{id}', ['uses' => 'TipoIdentificacionController@Edit', 'as' => 'TipoIdentificacionEdit']);
    Route::post('/TipoIdentificacion/actualizar/{id}', ['uses' => 'TipoIdentificacionController@Update', 'as' => 'TipoIdentificacionUpdate']);
    Route::post('/TipoIdentificacion/eliminar/{id}', ['uses' => 'TipoIdentificacionController@Delete', 'as' => 'TipoIdentificacionDelete']);
});
