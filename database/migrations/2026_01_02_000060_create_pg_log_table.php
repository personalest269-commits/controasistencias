<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * pg_log
 * - Guarda errores del sistema (excepciones / Log::error, etc.)
 * - Incluye datos de request y usuario para poder depurar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_log')) {
            Schema::create('pg_log', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('level', 20)->default('error');
                $table->string('channel', 50)->nullable();

                $table->text('message');
                $table->string('exception_class', 255)->nullable();
                $table->string('exception_code', 100)->nullable();
                $table->string('file', 255)->nullable();
                $table->integer('line')->nullable();
                $table->longText('trace')->nullable();

                // Contexto adicional (json si está disponible)
                if (DB::getDriverName() === 'mysql') {
                    $table->json('context')->nullable();
                } else {
                    $table->longText('context')->nullable();
                }

                $table->string('url', 2048)->nullable();
                $table->string('method', 10)->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 512)->nullable();

                // En este proyecto pg_usuario.id es VARCHAR(10)
                $table->string('usuario_id', 10)->nullable();

                // Estado: NULL = abierto, 'R' = resuelto, 'X' = eliminado
                $table->char('estado', 1)->nullable()->default(null);
                $table->timestamp('resolved_at')->nullable();
                $table->string('resolved_by', 10)->nullable();

                $table->timestamps();

                $table->index(['level']);
                $table->index(['estado']);
                $table->index(['usuario_id']);
                $table->index(['created_at']);
            });

            // FK best-effort
            try {
                if (Schema::hasTable('pg_usuario')) {
                    Schema::table('pg_log', function (Blueprint $table) {
                        $table->foreign('usuario_id', 'fk_pg_log_usuario')
                            ->references('id')->on('pg_usuario')
                            ->onUpdate('cascade')
                            ->onDelete('set null');
                    });
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Crear permiso para ver logs (si existe Entrust)
        try {
            if (Schema::hasTable('pg_permisos')) {
                DB::table('pg_permisos')->insertOrIgnore([
                    'name' => 'pg_log_all',
                    'display_name' => 'Logs del sistema',
                    'description' => 'Ver y gestionar logs de errores del sistema',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Asignar el permiso a Admin/Super-Admin por defecto
            if (Schema::hasTable('roles') && Schema::hasTable('pg_permisos') && Schema::hasTable('pg_permisos_role')) {
                $permId = DB::table('pg_permisos')->where('name', 'pg_log_all')->value('id');
                $roleIds = DB::table('roles')->whereIn('name', ['Admin', 'Super-Admin'])->pluck('id')->toArray();
                if ($permId && !empty($roleIds)) {
                    foreach ($roleIds as $rid) {
                        DB::table('pg_permisos_role')->insertOrIgnore([
                            'permission_id' => (int) $permId,
                            'role_id' => (int) $rid,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Crear opción de menú (nuevo menú pg_opcion_menu) y asignarla a roles (best-effort)
        try {
            if (
                Schema::hasTable('pg_opcion_menu')
                && Schema::hasTable('pg_opcion_menu_rol')
                && Schema::hasTable('roles')
            ) {
                $groupId = DB::table('pg_opcion_menu')
                    ->whereNull('estado')
                    ->whereNull('id_padre')
                    ->where('titulo', 'Administración')
                    ->value('id');

                if (!$groupId) {
                    $groupId = DB::table('pg_opcion_menu')->insertGetId([
                        'titulo' => 'Administración',
                        'id_padre' => null,
                        'url' => '#',
                        'tipo' => 'G',
                        'activo' => 'S',
                        'orden' => 999,
                        'id_archivo' => null,
                        'estado' => null,
                    ]);
                }

                $logMenuId = DB::table('pg_opcion_menu')
                    ->whereNull('estado')
                    ->where('id_padre', $groupId)
                    ->where('titulo', 'Logs del sistema')
                    ->value('id');

                if (!$logMenuId) {
                    $logMenuId = DB::table('pg_opcion_menu')->insertGetId([
                        'titulo' => 'Logs del sistema',
                        'id_padre' => $groupId,
                        // En el menú tipo M se usa route(<url>)
                        'url' => 'PgLogsIndex',
                        'tipo' => 'M',
                        'activo' => 'S',
                        'orden' => 999,
                        'id_archivo' => null,
                        'estado' => null,
                    ]);
                }

                // Asignar a roles Admin/Super-Admin
                $roleIds = DB::table('roles')->whereIn('name', ['Admin', 'Super-Admin'])->pluck('id')->toArray();

                // Si existe id_rol, usarlo; si no, usar columna "rol" (antigua)
                if (Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')) {
                    foreach ($roleIds as $rid) {
                        DB::table('pg_opcion_menu_rol')->insertOrIgnore([
                            'id_opcion_menu' => (int) $logMenuId,
                            'id_rol' => (int) $rid,
                            'estado' => null,
                        ]);
                    }
                } elseif (Schema::hasColumn('pg_opcion_menu_rol', 'rol')) {
                    $roleNames = DB::table('roles')->whereIn('id', $roleIds)->pluck('name')->toArray();
                    foreach ($roleNames as $rn) {
                        DB::table('pg_opcion_menu_rol')->insertOrIgnore([
                            'id_opcion_menu' => (int) $logMenuId,
                            'rol' => $rn,
                            'estado' => null,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // No eliminar por defecto (podría contener evidencia importante). Si deseas,
        // puedes borrar manualmente la tabla pg_log.
    }
};
