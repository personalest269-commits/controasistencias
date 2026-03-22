<?php

Route::group(['middleware' => ['web', 'auth', 'XSS', 'permission:pg_opcion_menu']], function () {
    Route::get('/OpcionesMenu', ['uses' => 'OpcionMenuController@Index', 'as' => 'OpcionMenuIndex']);
    Route::get('/OpcionesMenu/crear', ['uses' => 'OpcionMenuController@Create', 'as' => 'OpcionMenuCreate']);
    Route::post('/OpcionesMenu/guardar', ['uses' => 'OpcionMenuController@Store', 'as' => 'OpcionMenuStore']);
    Route::get('/OpcionesMenu/editar/{id}', ['uses' => 'OpcionMenuController@Edit', 'as' => 'OpcionMenuEdit']);
    Route::post('/OpcionesMenu/actualizar/{id}', ['uses' => 'OpcionMenuController@Update', 'as' => 'OpcionMenuUpdate']);
    Route::post('/OpcionesMenu/eliminar/{id}', ['uses' => 'OpcionMenuController@Delete', 'as' => 'OpcionMenuDelete']);
});
