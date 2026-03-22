<?php

Route::group(['middleware' => ['web', 'auth', 'XSS', 'permission:pg_configuraciones_all']], function () {
    Route::get('/PgConfiguraciones', ['uses' => 'PgConfiguracionesController@Index', 'as' => 'PgConfiguracionesIndex']);
});

Route::group(['middleware' => ['web', 'auth', 'XSS', 'permission:pg_configuraciones_update']], function () {
    Route::post('/PgConfiguraciones/guardar', ['uses' => 'PgConfiguracionesController@Update', 'as' => 'PgConfiguracionesUpdate']);
});
