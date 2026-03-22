<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: pg_configuraciones
 * - Parametriza comportamiento del sistema desde BD.
 * - Columnas en español.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_configuraciones')) {
            Schema::create('pg_configuraciones', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Ej: APP_TIMEZONE, FORMATO_FECHA, CORREO_ACTIVO
                $table->string('clave', 120)->unique();
                $table->longText('valor')->nullable();

                // texto | numero | booleano | archivo
                $table->string('tipo', 30)->default('texto');

                $table->string('descripcion', 255)->nullable();
                $table->string('grupo', 50)->default('general');

                // S/N
                $table->char('activo', 1)->default('S');

                // NULL = activo, X = eliminado
                $table->char('estado', 1)->nullable()->default(null);

                $table->timestamps();

                $table->index(['grupo']);
                $table->index(['activo']);
                $table->index(['estado']);
            });
        }

        // Seed inicial (best-effort, sin romper instalaciones existentes)
        try {
            $now = now();

            $defaults = [
                // General
                ['clave' => 'NOMBRE_SISTEMA', 'valor' => config('app.name', 'Sistema'), 'tipo' => 'texto', 'descripcion' => 'Nombre visible del sistema', 'grupo' => 'general', 'activo' => 'S'],
                ['clave' => 'APP_TIMEZONE', 'valor' => config('app.timezone', 'America/Guayaquil'), 'tipo' => 'texto', 'descripcion' => 'Zona horaria del sistema (PHP/Laravel)', 'grupo' => 'general', 'activo' => 'S'],
                ['clave' => 'FORMATO_FECHA', 'valor' => 'Y-m-d H:i:s', 'tipo' => 'texto', 'descripcion' => 'Formato para mostrar fechas (Carbon/PHP date format)', 'grupo' => 'general', 'activo' => 'S'],
                ['clave' => 'ITEMS_POR_PAGINA', 'valor' => '30', 'tipo' => 'numero', 'descripcion' => 'Cantidad de registros por página en listados', 'grupo' => 'general', 'activo' => 'S'],

                // Seguridad / acceso
                ['clave' => 'REGISTRO_USUARIO_ACTIVO', 'valor' => 'S', 'tipo' => 'booleano', 'descripcion' => 'Permitir registro de usuarios desde la pantalla de registro', 'grupo' => 'seguridad', 'activo' => 'S'],
                ['clave' => 'FRONTEND_ACTIVO', 'valor' => 'S', 'tipo' => 'booleano', 'descripcion' => 'Habilitar el frontend (sitio). Si está en N, redirige al login', 'grupo' => 'seguridad', 'activo' => 'S'],

                // Correo
                ['clave' => 'CORREO_ACTIVO', 'valor' => 'S', 'tipo' => 'booleano', 'descripcion' => 'Habilitar envío de correos del sistema', 'grupo' => 'correo', 'activo' => 'S'],

                // Apariencia
                ['clave' => 'LOGO_SISTEMA', 'valor' => '', 'tipo' => 'archivo', 'descripcion' => 'Logo del sistema (ruta en /public)', 'grupo' => 'apariencia', 'activo' => 'S'],
            ];

            foreach ($defaults as $d) {
                $exists = DB::table('pg_configuraciones')->where('clave', $d['clave'])->exists();
                if (!$exists) {
                    DB::table('pg_configuraciones')->insert([
                        'clave' => $d['clave'],
                        'valor' => $d['valor'],
                        'tipo' => $d['tipo'],
                        'descripcion' => $d['descripcion'],
                        'grupo' => $d['grupo'],
                        'activo' => $d['activo'],
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        } catch (Throwable $e) {
            // ignore
        }

        // Permisos + menú (best-effort)
        try {
            if (Schema::hasTable('pg_permisos')) {
                DB::table('pg_permisos')->insertOrIgnore([
                    'name' => 'pg_configuraciones_all',
                    'display_name' => 'Configuraciones del sistema - Ver',
                    'description' => 'Ver configuraciones del sistema (pg_configuraciones)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('pg_permisos')->insertOrIgnore([
                    'name' => 'pg_configuraciones_update',
                    'display_name' => 'Configuraciones del sistema - Actualizar',
                    'description' => 'Actualizar configuraciones del sistema (pg_configuraciones)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasTable('roles') && Schema::hasTable('pg_permisos') && Schema::hasTable('pg_permisos_role')) {
                $permIds = DB::table('pg_permisos')->whereIn('name', ['pg_configuraciones_all', 'pg_configuraciones_update'])->pluck('id')->toArray();
                $roleIds = DB::table('roles')->whereIn('name', ['Admin', 'Super-Admin'])->pluck('id')->toArray();
                foreach ($roleIds as $rid) {
                    foreach ($permIds as $pid) {
                        DB::table('pg_permisos_role')->insertOrIgnore([
                            'permission_id' => (int) $pid,
                            'role_id' => (int) $rid,
                        ]);
                    }
                }
            }

            if (
                Schema::hasTable('pg_opcion_menu')
                && Schema::hasTable('pg_opcion_menu_rol')
                && Schema::hasTable('roles')
            ) {
                // Buscar/crear grupo Administración
                $adminGroupId = DB::table('pg_opcion_menu')
                    ->whereNull('estado')
                    ->whereNull('id_padre')
                    ->where('titulo', 'Administración')
                    ->value('id');

                if (!$adminGroupId) {
                    $adminGroupId = DB::table('pg_opcion_menu')->insertGetId([
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

                // Crear/asegurar opción
                $menuId = DB::table('pg_opcion_menu')
                    ->whereNull('estado')
                    ->where('id_padre', $adminGroupId)
                    ->where('titulo', 'Configuraciones del sistema')
                    ->value('id');

                if (!$menuId) {
                    $menuId = DB::table('pg_opcion_menu')->insertGetId([
                        'titulo' => 'Configuraciones del sistema',
                        'id_padre' => (int) $adminGroupId,
                        'url' => 'PgConfiguracionesIndex',
                        'tipo' => 'M',
                        'activo' => 'S',
                        'orden' => 998,
                        'id_archivo' => null,
                        'estado' => null,
                    ]);
                }

                // Asignar a roles Admin/Super-Admin
                $roleIds = DB::table('roles')->whereIn('name', ['Admin', 'Super-Admin'])->pluck('id')->toArray();
                if (Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')) {
                    foreach ($roleIds as $rid) {
                        DB::table('pg_opcion_menu_rol')->insertOrIgnore([
                            'id_opcion_menu' => (int) $menuId,
                            'id_rol' => (int) $rid,
                            'estado' => null,
                        ]);
                    }
                }
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // No-op (no eliminar por seguridad)
    }
};
