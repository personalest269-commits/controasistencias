<?php

Route::group(['middleware' => ['web', 'auth', 'XSS', 'permission:fr_fronted_all']], function () {
    Route::get('/FrFronted', ['uses' => 'FrFrontedController@Index', 'as' => 'FrFrontedIndex']);
});

Route::group(['middleware' => ['web', 'auth', 'XSS', 'permission:fr_fronted_update']], function () {
    Route::post('/FrFronted/pagina', ['uses' => 'FrFrontedController@UpdatePagina', 'as' => 'FrFrontedUpdatePagina']);

    Route::post('/FrFronted/menu/guardar', ['uses' => 'FrFrontedController@SaveMenu', 'as' => 'FrFrontedSaveMenu']);
    Route::post('/FrFronted/menu/eliminar/{id}', ['uses' => 'FrFrontedController@DeleteMenu', 'as' => 'FrFrontedDeleteMenu']);

    Route::post('/FrFronted/seccion/guardar', ['uses' => 'FrFrontedController@SaveSeccion', 'as' => 'FrFrontedSaveSeccion']);
    Route::post('/FrFronted/seccion/eliminar/{id}', ['uses' => 'FrFrontedController@DeleteSeccion', 'as' => 'FrFrontedDeleteSeccion']);

    Route::post('/FrFronted/servicio/guardar', ['uses' => 'FrFrontedController@SaveServicio', 'as' => 'FrFrontedSaveServicio']);
    Route::post('/FrFronted/servicio/eliminar/{id}', ['uses' => 'FrFrontedController@DeleteServicio', 'as' => 'FrFrontedDeleteServicio']);

    Route::post('/FrFronted/portafolio/guardar', ['uses' => 'FrFrontedController@SavePortafolio', 'as' => 'FrFrontedSavePortafolio']);
    Route::post('/FrFronted/portafolio/eliminar/{id}', ['uses' => 'FrFrontedController@DeletePortafolio', 'as' => 'FrFrontedDeletePortafolio']);
});
