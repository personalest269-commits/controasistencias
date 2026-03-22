<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Models\Idioma;

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */
 Route::group(['prefix' => 'admin'], function (){
    Route::group(['middleware' => ['web']], function () {Route::group(['middleware' => ['web']], function () {

    Route::get('/CheckMigrationClass', 'ModuleBuilderController@CheckMigrationClass');
    //Route::get('auth/login', 'Auth\AuthController@getLogin');
    //Route::post('auth/login', 'Auth\AuthController@postLogin');
    //Route::get('auth/logout', 'Auth\AuthController@getLogout');
    Route::get('/login', array('uses' => 'UsersController@Login', 'as' => 'login'));
    Route::post('/login', array('uses' => 'UsersController@auth', 'as' => 'loginPost'));
    // Archivos digitales públicos (para login / logo / ilustraciones)
    // Nota: la ruta /ArchivosDigitales/ver/{id} está protegida por auth.
    Route::get('/public-file/{id}', ['uses' => 'ArchivosDigitalesController@PublicVer', 'as' => 'ArchivosDigitalesPublico']);
    // Cambiar idioma (para AdminLTE / Gentelella)
    Route::post('/lang', array('uses' => 'UsersController@setLang', 'as' => 'setLang'));
    // Cambiar interfaz (AdminLTE / Gentelella)
    Route::post('/ui-template', array('uses' => 'UsersController@setUiTemplate', 'as' => 'setUiTemplate'));
    Route::get('/register', array('uses' => 'UsersController@register', 'as' => 'register'));
    Route::post('/register', array('uses' => 'UsersController@RegisterPost', 'as' => 'registerPost'));
    Route::get('/install', array('uses' => 'InstallController@index'));
    Route::post('/install', array('uses' => 'InstallController@InstallProcess', 'as' => 'InstallProcess'));
    Route::get('/InstallstepTwo', array('uses' => 'InstallController@InstallStepTwo', 'as' => 'InstallStepTwo'));
    Route::post('/InstallMigration', array('uses' => 'InstallController@InstallMigration', 'as' => 'InstallMigration'));
    Route::get('/RegisterUserToAdmin', array('uses' => 'UsersController@RegisterUserToAdmin'));

    Route::get('privacy', 'UsersController@privacyPolicy');
    Route::get('login/facebook', array('uses' => 'UsersController@redirectToFacebookProvider', 'as' => 'facebookLogin'));
    Route::get('login/facebook/callback', 'UsersController@handleFacebookCallback');
    Route::get('login/google', array('uses' => 'UsersController@redirectToGoogleProvider', 'as' => 'googleLogin'));
    Route::get('login/google/callback', 'UsersController@handleGoogleCallback');
    Route::get('login/twitter', array('uses' => 'UsersController@redirectToTwitterProvider', 'as' => 'twitterLogin'));
    Route::get('login/twitter/callback', 'UsersController@handleTwitterCallback');
});

/*
|--------------------------------------------------------------------------
| Frontend: cambio de idioma (ES/EN) desde navbar
|--------------------------------------------------------------------------
|
| Usa la misma sesión que el admin: Session::get('lang') (middleware SetIdioma).
| Si existe tabla idiomas, valida que el código esté activo.
|
*/
Route::get('/lang/{lang}', function (string $lang) {
    $lang = strtolower(trim($lang));

    // Validar contra tabla idiomas (si existe)
    $allowed = ['es', 'en'];
    try {
        if (Schema::hasTable('idiomas')) {
            $allowed = Idioma::query()->where('activo', 1)->pluck('codigo')->map(fn($c) => strtolower((string)$c))->unique()->values()->all();
        }
    } catch (Throwable $e) {
        // ignore
    }

    if (!in_array($lang, $allowed, true)) {
        $lang = $allowed[0] ?? 'es';
    }

    Session::put('lang', $lang);

    // Persistir en cookie para visitantes no logueados
    return redirect()->back()->withCookie(cookie()->forever('lang', $lang));
})->name('lang.change');

Route::group(['middleware' => ['web', 'auth', 'XSS']], function () {
    // List - create - Edit/id - Update/id - Delete/
    //Users Routes
    require(base_path() . '/routes/users.php');
    require(base_path() . '/routes/GeneralSettings.php');
});
Route::group(['middleware' => ['web', 'auth', 'permission:filemanager']], function () {
    Route::get('/filemanage', array('uses' => 'AdminController@FileManage'));
});
Route::group(['middleware' => ['web', 'auth', 'XSS']], function () {
    //Mange Roles
    require(base_path() . '/routes/roles.php');
});
Route::group(['middleware' => ['web', 'auth','XSS']], function () {
    //Manage Permissions 
    require(base_path() . '/routes/permissions.php');
});
//Route::group(['namespace'=>'\App\Http\Controllers'], function () {
    Route::group(['middleware' => ['web', 'auth', 'permission:modulebuilder_modules|modulebuilder_menu']],function(){
        //ModuleBuilder
        require(base_path() . '/routes/modulebuilder.php');        
    });
//});
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/', ['uses' => 'AdminController@DashBoard','as'=>'dashboardIndex']);
    Route::get('/dashboard/events', ['uses' => 'AdminController@dashboardEvents', 'as' => 'dashboard.events']);

    // Importación de Personas (XLS / API) -> staging -> preview -> apply

// Gestión de Importaciones (Historial / Detalle / Logs)
Route::get('/importaciones', [\App\Http\Controllers\ImportacionesController::class, 'index'])->name('importaciones.index');
Route::get('/importaciones/{batch}', [\App\Http\Controllers\ImportacionesController::class, 'show'])->name('importaciones.show');
Route::get('/importaciones-logs', [\App\Http\Controllers\ImportacionesController::class, 'logs'])->name('importaciones.logs');

    Route::get('/personas/import', [\App\Http\Controllers\PersonaImportController::class, 'index'])->name('personas.import.index');
    Route::post('/personas/import/xls', [\App\Http\Controllers\PersonaImportController::class, 'importXls'])->name('personas.import.xls');
    Route::post('/personas/import/api', [\App\Http\Controllers\PersonaImportController::class, 'importApi'])->name('personas.import.api');
    Route::get('/personas/import/preview/{batch}', [\App\Http\Controllers\PersonaImportController::class, 'preview'])->name('personas.import.preview');
    Route::get('/personas/import/report/{batch}', [\App\Http\Controllers\PersonaImportController::class, 'report'])->name('personas.import.report');
    Route::post('/personas/import/apply/{batch}', [\App\Http\Controllers\PersonaImportController::class, 'apply'])->name('personas.import.apply');
    Route::post('/personas/import/clear/{batch}', [\App\Http\Controllers\PersonaImportController::class, 'clear'])->name('personas.import.clear');
    Route::post('/personas/import/truncate-stg', [\App\Http\Controllers\PersonaImportController::class, 'truncateStaging'])->name('personas.import.truncate_stg');

    // Configuración API (por defecto) para Importación de Personas
    // Protegido por permiso asignable por rol.
    Route::get('/config/api/personas-import', [\App\Http\Controllers\ApiConfigController::class, 'editPersonasImport'])
        ->middleware('permission:api_config_personas_import')
        ->name('api_config.personas_import.edit');
    Route::post('/config/api/personas-import', [\App\Http\Controllers\ApiConfigController::class, 'updatePersonasImport'])
        ->middleware('permission:api_config_personas_import')
        ->name('api_config.personas_import.update');

    Route::get('/logout', array('uses' => 'UsersController@Logout', 'as' => 'logout'));
    //Crud Routes
    require(base_path() . '/routes/WebCrudRoutes.php');
    //Facebook
    Route::get('/facebookTest', array('uses' => 'FacebookController@facebookTest', 'as' => 'FacebookTest'));
    Route::get('/pdftest', array('uses' => 'ModuleBuilderController@GeneratePDF', 'as' => 'GeneratePDF'));
    Route::get('/api-documentation', array('uses'=>'ApiDocumentationController@index','as'=>'ApiDocumentationIndex'));
});

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

 Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
     \UniSharp\LaravelFilemanager\Lfm::routes();
 });
 
 Route::get('/formbuilder', 'ModuleBuilderController@getFormBuilder');
 


    Route::get('/CheckMigrationClass', 'ModuleBuilderController@CheckMigrationClass');
    //Route::get('auth/login', 'Auth\AuthController@getLogin');
    //Route::post('auth/login', 'Auth\AuthController@postLogin');
    //Route::get('auth/logout', 'Auth\AuthController@getLogout');
    Route::get('/login', array('uses' => 'UsersController@Login', 'as' => 'login'));
    Route::post('/login', array('uses' => 'UsersController@auth', 'as' => 'loginPost'));
    Route::get('/register', array('uses' => 'UsersController@register', 'as' => 'register'));
    Route::post('/register', array('uses' => 'UsersController@RegisterPost', 'as' => 'registerPost'));
    Route::get('/install', array('uses' => 'InstallController@index'));
    Route::post('/install', array('uses' => 'InstallController@InstallProcess', 'as' => 'InstallProcess'));
    Route::get('/InstallstepTwo', array('uses' => 'InstallController@InstallStepTwo', 'as' => 'InstallStepTwo'));
    Route::post('/InstallMigration', array('uses' => 'InstallController@InstallMigration', 'as' => 'InstallMigration'));
    Route::get('/RegisterUserToAdmin', array('uses' => 'UsersController@RegisterUserToAdmin'));

    Route::get('privacy', 'UsersController@privacyPolicy');
    Route::get('login/facebook', array('uses' => 'UsersController@redirectToFacebookProvider', 'as' => 'facebookLogin'));
    Route::get('login/facebook/callback', 'UsersController@handleFacebookCallback');
    Route::get('login/google', array('uses' => 'UsersController@redirectToGoogleProvider', 'as' => 'googleLogin'));
    Route::get('login/google/callback', 'UsersController@handleGoogleCallback');
    Route::get('login/twitter', array('uses' => 'UsersController@redirectToTwitterProvider', 'as' => 'twitterLogin'));
    Route::get('login/twitter/callback', 'UsersController@handleTwitterCallback');
});

Route::group(['middleware' => ['web', 'auth', 'XSS']], function () {
    // List - create - Edit/id - Update/id - Delete/
    //Users Routes
    require(base_path() . '/routes/users.php');
    require(base_path() . '/routes/GeneralSettings.php');
});
Route::group(['middleware' => ['web', 'auth', 'permission:filemanager']], function () {
    Route::get('/filemanage', array('uses' => 'AdminController@FileManage'));
});
Route::group(['middleware' => ['web', 'auth', 'XSS']], function () {
    //Mange Roles
    require(base_path() . '/routes/roles.php');
});
Route::group(['middleware' => ['web', 'auth','XSS']], function () {
    //Manage Permissions 
    require(base_path() . '/routes/permissions.php');
});
Route::group(['middleware' => ['web', 'auth', 'permission:modulebuilder_modules|modulebuilder_menu', 'XSS']], function () {
    //ModuleBuilder
    require(base_path() . '/routes/modulebuilder.php');
});
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/', ['uses' => 'AdminController@DashBoard','as'=>'dashboardIndex']);
    Route::get('/logout', array('uses' => 'UsersController@Logout', 'as' => 'logout'));
    //Crud Routes
    require(base_path() . '/routes/WebCrudRoutes.php');
    //Facebook
    Route::get('/facebookTest', array('uses' => 'FacebookController@facebookTest', 'as' => 'FacebookTest'));
    Route::get('/pdftest', array('uses' => 'ModuleBuilderController@GeneratePDF', 'as' => 'GeneratePDF'));
    Route::get('/api-documentation', array('uses'=>'ApiDocumentationController@index','as'=>'ApiDocumentationIndex'));
});

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

 Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
     \UniSharp\LaravelFilemanager\Lfm::routes();
 });
 
 Route::get('/formbuilder', 'ModuleBuilderController@getFormBuilder');
 
 Route::group(['middleware' => ['web']], function () {

    Route::get('/CheckMigrationClass', 'ModuleBuilderController@CheckMigrationClass');
    //Route::get('auth/login', 'Auth\AuthController@getLogin');
    //Route::post('auth/login', 'Auth\AuthController@postLogin');
    //Route::get('auth/logout', 'Auth\AuthController@getLogout');
    Route::get('/login', array('uses' => 'UsersController@Login', 'as' => 'login'));
    Route::post('/login', array('uses' => 'UsersController@auth', 'as' => 'loginPost'));
    Route::get('/register', array('uses' => 'UsersController@register', 'as' => 'register'));
    Route::post('/register', array('uses' => 'UsersController@RegisterPost', 'as' => 'registerPost'));
    Route::get('/install', array('uses' => 'InstallController@index'));
    Route::post('/install', array('uses' => 'InstallController@InstallProcess', 'as' => 'InstallProcess'));
    Route::get('/InstallstepTwo', array('uses' => 'InstallController@InstallStepTwo', 'as' => 'InstallStepTwo'));
    Route::post('/InstallMigration', array('uses' => 'InstallController@InstallMigration', 'as' => 'InstallMigration'));
    Route::get('/RegisterUserToAdmin', array('uses' => 'UsersController@RegisterUserToAdmin'));

    Route::get('privacy', 'UsersController@privacyPolicy');
    Route::get('login/facebook', array('uses' => 'UsersController@redirectToFacebookProvider', 'as' => 'facebookLogin'));
    Route::get('login/facebook/callback', 'UsersController@handleFacebookCallback');
    Route::get('login/google', array('uses' => 'UsersController@redirectToGoogleProvider', 'as' => 'googleLogin'));
    Route::get('login/google/callback', 'UsersController@handleGoogleCallback');
    Route::get('login/twitter', array('uses' => 'UsersController@redirectToTwitterProvider', 'as' => 'twitterLogin'));
    Route::get('login/twitter/callback', 'UsersController@handleTwitterCallback');
});

Route::group(['middleware' => ['web', 'auth', 'XSS']], function () {
    // List - create - Edit/id - Update/id - Delete/
    //Users Routes
    require(base_path() . '/routes/users.php');
    require(base_path() . '/routes/GeneralSettings.php');
});
Route::group(['middleware' => ['web', 'auth', 'permission:filemanager']], function () {
    Route::get('/filemanage', array('uses' => 'AdminController@FileManage'));
});
Route::group(['middleware' => ['web', 'auth', 'XSS']], function () {
    //Mange Roles
    require(base_path() . '/routes/roles.php');
});
Route::group(['middleware' => ['web', 'auth','XSS']], function () {
    //Manage Permissions 
    require(base_path() . '/routes/permissions.php');
});
Route::group(['middleware' => ['web', 'auth', 'permission:modulebuilder_modules|modulebuilder_menu', 'XSS']], function () {
    //ModuleBuilder
    require(base_path() . '/routes/modulebuilder.php');
});
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/', ['uses' => 'AdminController@DashBoard','as'=>'dashboardIndex']);
    Route::get('/logout', array('uses' => 'UsersController@Logout', 'as' => 'logout'));
    //Crud Routes
    require(base_path() . '/routes/WebCrudRoutes.php');
    //Facebook
    Route::get('/facebookTest', array('uses' => 'FacebookController@facebookTest', 'as' => 'FacebookTest'));
    Route::get('/pdftest', array('uses' => 'ModuleBuilderController@GeneratePDF', 'as' => 'GeneratePDF'));
    Route::get('/api-documentation', array('uses'=>'ApiDocumentationController@index','as'=>'ApiDocumentationIndex'));
});

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

 Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
     \UniSharp\LaravelFilemanager\Lfm::routes();
 });
 
 Route::get('/formbuilder', 'ModuleBuilderController@getFormBuilder');
});

 Route::get('/', 'App\Http\Controllers\SiteController@home')->name('site');
 Route::get('/blog-categories', 'App\Http\Controllers\SiteController@blogCategories');
 Route::get('/blog-categories/{id}', 'App\Http\Controllers\SiteController@blogCategory')->name('blogCategory');
 Route::get('/blogs/', 'App\Http\Controllers\SiteController@blogs')->name('blogs');
 Route::get('/blogs/{id}', 'App\Http\Controllers\SiteController@singleBlog')->name('singleBlog');

Route::group(['prefix' => 'admin', 'middleware' => ['web', 'auth']], function () {
    Route::get('/license-client', [\App\Http\Controllers\LicenseClientController::class, 'index'])->name('license-client.index');
    Route::post('/license-client/save', [\App\Http\Controllers\LicenseClientController::class, 'save'])->name('license-client.save');
    Route::post('/license-client/validate', [\App\Http\Controllers\LicenseClientController::class, 'validateNow'])->name('license-client.validate');
    Route::post('/license-client/activate', [\App\Http\Controllers\LicenseClientController::class, 'activate'])->name('license-client.activate');
    Route::post('/license-client/status', [\App\Http\Controllers\LicenseClientController::class, 'consult'])->name('license-client.status');
    Route::post('/license-client/deactivate', [\App\Http\Controllers\LicenseClientController::class, 'deactivate'])->name('license-client.deactivate');
    Route::post('/license-client/check-updates', [\App\Http\Controllers\LicenseClientController::class, 'checkUpdates'])->name('license-client.check-updates');
    Route::post('/license-client/download-update', [\App\Http\Controllers\LicenseClientController::class, 'downloadUpdate'])->name('license-client.download-update');
    Route::post('/license-client/apply-update', [\App\Http\Controllers\LicenseClientController::class, 'applyUpdate'])->name('license-client.apply-update');
});
