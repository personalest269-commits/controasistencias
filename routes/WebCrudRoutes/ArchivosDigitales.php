<?php

Route::group(['middleware' => ['web', 'auth', 'XSS']], function () {
    Route::get('/ArchivosDigitales', ['uses' => 'ArchivosDigitalesController@Index', 'as' => 'ArchivosDigitalesIndex']);
    Route::get('/ArchivosDigitales/ver/{id}', ['uses' => 'ArchivosDigitalesController@Ver', 'as' => 'ArchivosDigitalesVer']);
    Route::get('/ArchivosDigitales/crear', ['uses' => 'ArchivosDigitalesController@Create', 'as' => 'ArchivosDigitalesCreate']);
    Route::post('/ArchivosDigitales/guardar', ['uses' => 'ArchivosDigitalesController@Store', 'as' => 'ArchivosDigitalesStore']);
    Route::get('/ArchivosDigitales/editar/{id}', ['uses' => 'ArchivosDigitalesController@Edit', 'as' => 'ArchivosDigitalesEdit']);
    Route::post('/ArchivosDigitales/actualizar/{id}', ['uses' => 'ArchivosDigitalesController@Update', 'as' => 'ArchivosDigitalesUpdate']);
    Route::post('/ArchivosDigitales/eliminar/{id}', ['uses' => 'ArchivosDigitalesController@Delete', 'as' => 'ArchivosDigitalesDelete']);
});
