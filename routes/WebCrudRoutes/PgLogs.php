<?php

Route::group(['middleware' => ['web', 'auth', 'XSS', 'permission:pg_log_all']], function () {
    Route::get('/PgLogs', ['uses' => 'PgLogsController@Index', 'as' => 'PgLogsIndex']);
    Route::get('/PgLogs/ver/{id}', ['uses' => 'PgLogsController@Show', 'as' => 'PgLogsShow']);
    Route::post('/PgLogs/resolver/{id}', ['uses' => 'PgLogsController@Resolve', 'as' => 'PgLogsResolve']);
    Route::post('/PgLogs/eliminar/{id}', ['uses' => 'PgLogsController@Delete', 'as' => 'PgLogsDelete']);
});
