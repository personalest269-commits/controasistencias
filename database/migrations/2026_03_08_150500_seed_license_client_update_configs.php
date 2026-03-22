<?php

use App\Models\PgConfiguracion;
use App\Services\ExternalLicenseService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (!class_exists(PgConfiguracion::class) || !class_exists(ExternalLicenseService::class)) {
            return;
        }
        app(ExternalLicenseService::class)->ensureSeeded();
    }

    public function down(): void
    {
        // No borrar configuraciones para conservar estado local del cliente de licencias.
    }
};
