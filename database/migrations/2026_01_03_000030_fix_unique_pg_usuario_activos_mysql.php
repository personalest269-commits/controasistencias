<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        // Solo MySQL/MariaDB
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Helper: dropear índices/columnas si existen
        $dropIndex = function (string $indexName) {
            try {
                DB::statement("DROP INDEX `{$indexName}` ON `pg_usuario`");
            } catch (\Throwable $e) {
                // ignore
            }
        };

        // 1) Eliminar estado_norm si existiera (ya no se usa)
        try {
            if (Schema::hasColumn('pg_usuario', 'estado_norm')) {
                // Antes de dropear la columna, intentamos dropear índices que la referencien.
                $dropIndex('pg_usuario_email_activo_unique');
                $dropIndex('pg_usuario_id_persona_activo_unique');
                $dropIndex('pg_usuario_usuario_activo_unique');
                try {
                    DB::statement('ALTER TABLE pg_usuario DROP COLUMN estado_norm');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 2) Asegurar columna usuario (si viene de versiones anteriores)
        try {
            if (!Schema::hasColumn('pg_usuario', 'usuario')) {
                DB::statement('ALTER TABLE pg_usuario ADD COLUMN usuario VARCHAR(10) NULL');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 3) Quitar uniques viejos (email/id_persona global)
        $dropIndex('pg_usuario_email_unique');
        $dropIndex('email');
        $dropIndex('uk_pg_usuario_id_persona');
        $dropIndex('pg_usuario_id_persona_unique');

        // 4) Crear uniques "solo activos" usando índice funcional (sin estado_norm)
        // Activo => IFNULL(estado,'A') = 'A'
        $dropIndex('pg_usuario_usuario_activo_unique');
        $dropIndex('pg_usuario_id_persona_activo_unique');

        try {
            DB::statement("CREATE UNIQUE INDEX pg_usuario_usuario_activo_unique ON pg_usuario (usuario, (IFNULL(estado,'A')))");
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            DB::statement("CREATE UNIQUE INDEX pg_usuario_id_persona_activo_unique ON pg_usuario (id_persona, (IFNULL(estado,'A')))");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pg_usuario')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try { DB::statement("DROP INDEX `pg_usuario_usuario_activo_unique` ON `pg_usuario`"); } catch (\Throwable $e) {}
        try { DB::statement("DROP INDEX `pg_usuario_id_persona_activo_unique` ON `pg_usuario`"); } catch (\Throwable $e) {}
    }
};
