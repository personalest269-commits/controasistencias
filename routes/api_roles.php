<?php
Route::group(['middleware' => ['auth','permission:roles_all']],function(){
    Route::get('/users/getroles', array('uses' => 'RolesController@GetRoles', 'as' => 'api_getroles'));
    });
Route::group(['middleware' => ['auth','permission:roles_edit']],function(){
    Route::get('/roles/edit/{id}', array('uses' => 'RolesController@edit', 'as' => 'api_rolesedit'));
    });
Route::group(['middleware' => ['auth','permission:roles_create_update']],function(){
    Route::post('/roles/createorupdate', array('uses' => 'RolesController@CreateOrUpdate', 'as' => 'api_rolescreateorupdate'));
    });
Route::group(['middleware' => ['auth','permission:roles_delete']],function(){
    Route::get('/roles/delete/{id}', array('uses' => 'RolesController@Delete', 'as' => 'api_rolesdelete'));
});
Route::group(['middleware' => ['auth','permission:roles_delete_multiple']],function(){
    Route::delete('/roles/deletemultiple', array('uses' => 'RolesController@DeleteMultiple', 'as' => 'api_rolesdeletemultiple'));
});