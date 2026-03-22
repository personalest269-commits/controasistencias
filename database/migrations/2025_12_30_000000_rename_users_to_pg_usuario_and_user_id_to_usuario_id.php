<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Renombrar users -> pg_usuario (si existe)
        if (Schema::hasTable('users') && !Schema::hasTable('pg_usuario')) {
            Schema::rename('users', 'pg_usuario');
        }

        // 2) Renombrar role_user.user_id -> role_user.usuario_id y ajustar FK/PK
        if (Schema::hasTable('role_user')) {
            $driver = DB::connection()->getDriverName();

            // Dropear FK/PK antiguas si existen
            if (Schema::hasColumn('role_user', 'user_id')) {
                $this->dropRoleUserUserFk($driver, 'role_user_user_id_foreign');
                $this->dropRoleUserPk($driver);
                $this->renameRoleUserColumn($driver, 'user_id', 'usuario_id');
            }

            // Si ya existe usuario_id pero está apuntando a users, igual recreamos la FK
            if (Schema::hasColumn('role_user', 'usuario_id')) {
                $this->dropRoleUserUserFk($driver, 'role_user_usuario_id_foreign');
                // Crear FK hacia pg_usuario
                try {
                    Schema::table('role_user', function (Blueprint $table) {
                        $table->foreign('usuario_id')
                            ->references('id')->on('pg_usuario')
                            ->onUpdate('cascade')->onDelete('cascade');
                    });
                } catch (\Throwable $e) {
                    // Si ya existe, no hacemos nada
                }

                // Recrear PK compuesta
                $this->ensureRoleUserPk($driver);
            }
        }
    }

    public function down(): void
    {
        // Revertir role_user.usuario_id -> role_user.user_id
        if (Schema::hasTable('role_user')) {
            $driver = DB::connection()->getDriverName();

            if (Schema::hasColumn('role_user', 'usuario_id')) {
                $this->dropRoleUserUserFk($driver, 'role_user_usuario_id_foreign');
                $this->dropRoleUserPk($driver);
                $this->renameRoleUserColumn($driver, 'usuario_id', 'user_id');

                // FK a users (si existe)
                if (Schema::hasTable('users')) {
                    try {
                        Schema::table('role_user', function (Blueprint $table) {
                            $table->foreign('user_id')
                                ->references('id')->on('users')
                                ->onUpdate('cascade')->onDelete('cascade');
                        });
                    } catch (\Throwable $e) {
                        // noop
                    }
                }
                $this->ensureRoleUserPk($driver, 'user_id');
            }
        }

        // Revertir pg_usuario -> users
        if (Schema::hasTable('pg_usuario') && !Schema::hasTable('users')) {
            Schema::rename('pg_usuario', 'users');
        }
    }

    private function dropRoleUserUserFk(string $driver, string $constraintName): void
    {
        try {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE role_user DROP FOREIGN KEY {$constraintName}");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE role_user DROP CONSTRAINT IF EXISTS {$constraintName}");
            } else {
                // sqlite / otros: mejor esfuerzo
            }
        } catch (\Throwable $e) {
            // noop
        }
    }

    private function dropRoleUserPk(string $driver): void
    {
        try {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE role_user DROP PRIMARY KEY');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE role_user DROP CONSTRAINT IF EXISTS role_user_pkey');
            } else {
                // sqlite / otros
            }
        } catch (\Throwable $e) {
            // noop
        }
    }

    private function renameRoleUserColumn(string $driver, string $from, string $to): void
    {
        try {
            if ($driver === 'mysql') {
                // role_user.{from} es bigInteger unsigned
                DB::statement("ALTER TABLE role_user CHANGE {$from} {$to} BIGINT UNSIGNED NOT NULL");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE role_user RENAME COLUMN {$from} TO {$to}");
            } else {
                // sqlite / otros: fallback a schema builder (puede requerir doctrine)
                Schema::table('role_user', function (Blueprint $table) use ($from, $to) {
                    $table->renameColumn($from, $to);
                });
            }
        } catch (\Throwable $e) {
            // noop
        }
    }

    private function ensureRoleUserPk(string $driver, string $userKey = 'usuario_id'): void
    {
        try {
            if ($driver === 'mysql' || $driver === 'pgsql') {
                DB::statement("ALTER TABLE role_user ADD PRIMARY KEY ({$userKey}, role_id)");
            } else {
                // sqlite / otros
            }
        } catch (\Throwable $e) {
            // noop
        }
    }
};
