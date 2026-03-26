<?php

namespace App\Providers;

use App\Models\EmailSetting;
use Laravel\Passport\Console\ClientCommand;
use Laravel\Passport\Console\InstallCommand;
use Laravel\Passport\Console\KeysCommand;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use App\Models\PgConfiguracion;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Schema::defaultStringLength(191);

        // Helpers de traducción DB (tr(), pg_t())
        $helper = app_path('Helpers/pg_translate.php');
        if (file_exists($helper)) {
            require_once $helper;
        }

        // Helpers de fechas (pg_fecha(), pg_fecha_solo(), etc.)
        $helperFecha = app_path('Helpers/pg_fecha.php');
        if (file_exists($helperFecha)) {
            require_once $helperFecha;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Permite sobreescribir la conexión mysql_archivos desde pg_configuraciones_bases.
        try {
            if (Schema::hasTable('pg_configuraciones_bases')) {
                $cfgBaseArchivos = DB::table('pg_configuraciones_bases')
                    ->where('nombre', 'conexion_archivos_mysql')
                    ->where('driver', 'mysql')
                    ->where('activo', 'S')
                    ->whereNull('estado')
                    ->first();

                if ($cfgBaseArchivos) {
                    $baseConnection = (array) config('database.connections.mysql_archivos', []);
                    $baseConnection['driver'] = $cfgBaseArchivos->driver ?: ($baseConnection['driver'] ?? 'mysql');
                    $baseConnection['host'] = $cfgBaseArchivos->host ?: ($baseConnection['host'] ?? '127.0.0.1');
                    $baseConnection['port'] = $cfgBaseArchivos->port ?: ($baseConnection['port'] ?? '3306');
                    $baseConnection['database'] = $cfgBaseArchivos->database ?: ($baseConnection['database'] ?? null);
                    $baseConnection['schema'] = $cfgBaseArchivos->schema ?: ($baseConnection['schema'] ?? null);
                    $baseConnection['username'] = $cfgBaseArchivos->username ?: ($baseConnection['username'] ?? null);
                    $baseConnection['password'] = $cfgBaseArchivos->password ?: ($baseConnection['password'] ?? null);
                    $baseConnection['charset'] = $cfgBaseArchivos->charset ?: ($baseConnection['charset'] ?? 'utf8mb4');
                    $baseConnection['collation'] = $cfgBaseArchivos->collation ?: ($baseConnection['collation'] ?? 'utf8mb4_unicode_ci');

                    Config::set('database.connections.mysql_archivos', $baseConnection);

                    // Si ya estaba resuelta la conexión en este request, forzamos recarga.
                    DB::purge('mysql_archivos');
                }
            }
        } catch (\Throwable $e) {
            // Ignore during installs/migrations when DB may not be ready.
        }

        // Aplicar configuraciones del sistema desde BD (timezone, nombre, etc.)
        try {
            PgConfiguracion::applyRuntime();
            $pgcfg = PgConfiguracion::allKeyValue();
            view()->share('pgcfg', $pgcfg);
            view()->share('pg_date_format', PgConfiguracion::formatoFecha());
            view()->share('pg_date_format_solo', PgConfiguracion::formatoFechaSolo());
            view()->share('pg_date_placeholder', PgConfiguracion::placeholderFecha());
            view()->share('pg_date_placeholder_solo', PgConfiguracion::placeholderFechaSolo());
            view()->share('app_logo_url', PgConfiguracion::logoUrl());
            view()->share('login_illus_left_url', PgConfiguracion::loginIllusLeftUrl());
            view()->share('login_illus_right_url', PgConfiguracion::loginIllusRightUrl());
        } catch (\Throwable $e) {
            // ignore
        }

        // Blade helpers: @fecha($dt) y @fecha_solo($d)
        try {
            Blade::directive('fecha', function ($expression) {
                return "<?php echo \\App\\Models\\PgConfiguracion::formatFecha({$expression}); ?>";
            });
            Blade::directive('fecha_solo', function ($expression) {
                return "<?php echo \\App\\Models\\PgConfiguracion::formatFechaSolo({$expression}); ?>";
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // AdminLTE usa Bootstrap; hacemos que la paginación de Laravel renderice con Bootstrap.
        Paginator::useBootstrapFive();

        // Ensure the application locale follows the system setting (.env locale / APP_LOCALE).
        app()->setLocale(config('app.locale'));

        // Dynamically apply system email settings (editable from the admin panel).
        try {
            if (Schema::hasTable('email_configuraciones')) {
                $settings = EmailSetting::query()->first();
                if ($settings) {
                    $driver = $settings->mail_driver ?: 'smtp';

                    // Laravel 10 mail config keys.
                    Config::set('mail.default', $driver);
                    Config::set('mail.mailers.' . $driver . '.host', $settings->mail_host);
                    Config::set('mail.mailers.' . $driver . '.port', $settings->mail_port);
                    Config::set('mail.mailers.' . $driver . '.username', $settings->mail_username);
                    Config::set('mail.mailers.' . $driver . '.password', $settings->mail_password);
                    Config::set('mail.mailers.' . $driver . '.encryption', $settings->mail_encryption);

                    Config::set('mail.from.address', $settings->mail_from_address);
                    Config::set('mail.from.name', $settings->mail_from_name);

                    // Important: the MailManager caches mailer instances. If it was already resolved
                    // earlier in the request lifecycle (by another provider), force it to reload.
                    if ($this->app->resolved('mail.manager')) {
                        $this->app->make('mail.manager')->forgetMailers();
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore during installs/migrations when DB may not be ready.
        }

        $this->commands([
            InstallCommand::class,
            ClientCommand::class,
            KeysCommand::class,
        ]);
    }
}
