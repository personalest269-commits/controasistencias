<?php
Route::group(['middleware' => ['auth','permission:permissions_all']],function(){
    Route::get('/users/getpermissions', array('uses' => 'PermissionsController@GetPermissions', 'as' => 'api_getpermissions'));
});
Route::group(['middleware' => ['auth','permission:permissions_edit']],function(){
    Route::get('/permissions/edit/{id}', array('uses' => 'PermissionsController@edit', 'as' => 'api_permissionsedit'));
});
Route::group(['middleware' => ['auth','permission:permissions_create_update']],function(){
    Route::post('/permissions/createorupdate', array('uses' => 'PermissionsController@CreateOrUpdate', 'as' => 'api_permissionscreateorupdate'));
});
Route::group(['middleware' => ['auth','permission:permissions_delete']],function(){
    Route::get('/permissions/delete/{id}', array('uses' => 'PermissionsController@Delete', 'as' => 'api_permissionsdelete'));
});
Route::group(['middleware' => ['auth','permission:permissions_delete_multiple']],function(){
    Route::delete('/permissions/deletemultiple', array('uses' => 'PermissionsController@DeleteMultiple', 'as' => 'api_permissionsdeletemultiple'));
});