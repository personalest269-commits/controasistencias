<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Este proyecto usa MySQL (XAMPP) y la PK de usuarios es VARCHAR(10) (pg_usuario.id).
     *
     * En algunas instalaciones de Passport (versiones recientes), los IDs de oauth_clients
     * se generan como UUID (36 chars). Si en tu BD la tabla oauth_clients quedó con id BIGINT
     * (instalación antigua), al ejecutar `php artisan passport:install` se produce:
     *   "Data truncated for column 'id'".
     *
     * Este parche alinea el esquema de tablas oauth_* a:
     *  - oauth_clients.id => VARCHAR(36)
     *  - oauth_* .client_id => VARCHAR(36)
     *  - oauth_* .user_id => VARCHAR(10) (para que coincida con pg_usuario.id)
     */
    public function up(): void
    {
        // Si Passport no está instalado / no existen las tablas, no hacemos nada.
        if (!Schema::hasTable('oauth_clients')) {
            return;
        }

        // oauth_clients.id
        $this->tryStatement("ALTER TABLE oauth_clients MODIFY id VARCHAR(36) NOT NULL");

        // Tablas relacionadas (client_id)
        if (Schema::hasTable('oauth_personal_access_clients')) {
            $this->tryStatement("ALTER TABLE oauth_personal_access_clients MODIFY client_id VARCHAR(36) NOT NULL");
        }

        if (Schema::hasTable('oauth_auth_codes')) {
            $this->tryStatement("ALTER TABLE oauth_auth_codes MODIFY client_id VARCHAR(36) NOT NULL");
            $this->tryStatement("ALTER TABLE oauth_auth_codes MODIFY user_id VARCHAR(10) NULL");
        }

        if (Schema::hasTable('oauth_access_tokens')) {
            $this->tryStatement("ALTER TABLE oauth_access_tokens MODIFY client_id VARCHAR(36) NOT NULL");
            $this->tryStatement("ALTER TABLE oauth_access_tokens MODIFY user_id VARCHAR(10) NULL");
        }

        // oauth_refresh_tokens no tiene client_id ni user_id; no requiere cambios.
    }

    public function down(): void
    {
        // No revertimos automáticamente: podría romper instalaciones ya migradas.
    }

    private function tryStatement(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            // Silencioso: evita romper despliegues donde ya está corregido o el motor es distinto.
        }
    }
};
