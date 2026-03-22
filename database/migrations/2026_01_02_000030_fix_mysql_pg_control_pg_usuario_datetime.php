<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo aplica para MySQL/MariaDB
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1) Forzar tipo numérico para pg_control.ultimo (evita errores tipo SQLSTATE[22007])
        if (Schema::hasTable('pg_control')) {
            try {
                DB::statement('ALTER TABLE pg_control MODIFY ultimo BIGINT UNSIGNED NOT NULL DEFAULT 0');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 2) Asegurar columnas datetime en pg_usuario
        if (Schema::hasTable('pg_usuario')) {
            try {
                if (Schema::hasColumn('pg_usuario', 'created_at')) {
                    DB::statement('ALTER TABLE pg_usuario MODIFY created_at DATETIME NULL');
                }
                if (Schema::hasColumn('pg_usuario', 'updated_at')) {
                    DB::statement('ALTER TABLE pg_usuario MODIFY updated_at DATETIME NULL');
                }
                if (Schema::hasColumn('pg_usuario', 'email_verified_at')) {
                    DB::statement('ALTER TABLE pg_usuario MODIFY email_verified_at DATETIME NULL');
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        // No revertimos: este ajuste es correctivo.
    }
};
