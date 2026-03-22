<?php
Route::group(['middleware' => ['web', 'auth', 'permission:general_settings_all']], function () {
    Route::get('/general-settings', array('uses' => 'GeneralSettingsController@index', 'as' => 'general-settings'));

    // Login Settings
    Route::get('/login-settings', array('uses' => 'LoginSettingsController@index', 'as' => 'login-settings'));

    // Email Settings
    Route::get('/email-settings', array('uses' => 'EmailSettingsController@index', 'as' => 'email-settings'));

    // Email Templates
    Route::get('/email-templates', array('uses' => 'EmailTemplatesController@index', 'as' => 'email-templates'));
    Route::get('/email-templates/{slug}', array('uses' => 'EmailTemplatesController@edit', 'as' => 'email-templates.edit'));
});
Route::group(['middleware' => ['web', 'auth', 'permission:general_settings_create_update']], function () {
    Route::post('/general-settings/create_or_update', array('uses' => 'GeneralSettingsController@CreateOrUpdate', 'as' => 'GeneralSettingscreateorupdate'));

    // Login Settings
    Route::post('/login-settings/update', array('uses' => 'LoginSettingsController@update', 'as' => 'login-settings.update'));

    // Email Settings
    Route::post('/email-settings/update', array('uses' => 'EmailSettingsController@update', 'as' => 'email-settings.update'));
    Route::post('/email-settings/test', array('uses' => 'EmailSettingsController@sendTest', 'as' => 'email-settings.test'));

    // Email Templates
    Route::post('/email-templates/{slug}', array('uses' => 'EmailTemplatesController@update', 'as' => 'email-templates.update'));
});
