<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datos por defecto para el frontend (creative).
 *
 * Importante: se insertan con IDs fijos 0000000001, 0000000002, ...
 * y se actualiza pg_control para que los triggers / IdGenerator continúen
 * generando consecutivos sin colisiones.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fr_pagina_inicio')) {
            return;
        }

        $now = now();

        // Página (1 registro)
        $existsPagina = DB::table('fr_pagina_inicio')->whereNull('estado')->exists();
        if (!$existsPagina) {
            DB::table('fr_pagina_inicio')->insert([
                'id' => '0000000001',
                'nombre_sitio_es' => 'Control de Asistencia Municipal',
                'nombre_sitio_en' => 'Municipal Attendance Control',
                'logo_archivo_id' => null,
                'hero_titulo_es' => 'Control de Asistencia Municipal',
                'hero_titulo_en' => 'Municipal Attendance Control',
                'hero_subtitulo_es' => 'Sistema de control de asistencias y reportes.',
                'hero_subtitulo_en' => 'Attendance control and reports system.',
                'hero_boton_texto_es' => 'Ingresar',
                'hero_boton_texto_en' => 'Login',
                'hero_boton_url' => '/admin/login',
                'hero_fondo_archivo_id' => null,
                'contacto_telefono' => '',
                'contacto_email' => '',
                'contacto_direccion_es' => '',
                'contacto_direccion_en' => '',
                'cookies_activo' => 'N',
                'cookies_texto_es' => 'Usamos cookies para mejorar tu experiencia.',
                'cookies_texto_en' => 'We use cookies to improve your experience.',
                'cookies_btn_aceptar_es' => 'Aceptar',
                'cookies_btn_aceptar_en' => 'Accept',
                'cookies_btn_rechazar_es' => 'Rechazar',
                'cookies_btn_rechazar_en' => 'Reject',
                'estado' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Menú
        if (Schema::hasTable('fr_menu')) {
            $existsMenu = DB::table('fr_menu')->whereNull('estado')->exists();
            if (!$existsMenu) {
                DB::table('fr_menu')->insert([
                    [
                        'id' => '0000000001',
                        'orden' => 1,
                        'texto_es' => 'Acerca de',
                        'texto_en' => 'About',
                        'tipo' => 'anchor',
                        'destino' => '#about',
                        'nuevo_tab' => 'N',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000002',
                        'orden' => 2,
                        'texto_es' => 'Servicios',
                        'texto_en' => 'Services',
                        'tipo' => 'anchor',
                        'destino' => '#services',
                        'nuevo_tab' => 'N',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000003',
                        'orden' => 3,
                        'texto_es' => 'Portafolio',
                        'texto_en' => 'Portfolio',
                        'tipo' => 'anchor',
                        'destino' => '#portfolio',
                        'nuevo_tab' => 'N',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000004',
                        'orden' => 4,
                        'texto_es' => 'Blog',
                        'texto_en' => 'Blog',
                        'tipo' => 'route',
                        'destino' => 'blogs',
                        'nuevo_tab' => 'N',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000005',
                        'orden' => 5,
                        'texto_es' => 'Contacto',
                        'texto_en' => 'Contact',
                        'tipo' => 'anchor',
                        'destino' => '#contact',
                        'nuevo_tab' => 'N',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);
            }
        }

        // Secciones
        if (Schema::hasTable('fr_seccion')) {
            $existsSec = DB::table('fr_seccion')->whereNull('estado')->exists();
            if (!$existsSec) {
                // IMPORTANTE (MySQL/Laravel): todos los registros deben incluir las mismas columnas.
                // Si un registro omite una columna, MySQL lanzará "Column count doesn't match value count".
                DB::table('fr_seccion')->insert([
                    [
                        'id' => '0000000001',
                        'codigo' => 'about',
                        'orden' => 1,
                        'mostrar' => 'S',
                        'titulo_es' => '¡Tenemos lo que necesitas!',
                        'titulo_en' => "We've got what you need!",
                        'subtitulo_es' => null,
                        'subtitulo_en' => null,
                        'contenido_es' => 'Gestiona asistencias, justificaciones y reportes de manera centralizada.',
                        'contenido_en' => 'Manage attendance, justifications and reports in one place.',
                        'boton_texto_es' => 'Ver servicios',
                        'boton_texto_en' => 'View services',
                        'boton_url' => '#services',
                        'clase_css' => 'bg-primary',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000002',
                        'codigo' => 'services',
                        'orden' => 2,
                        'mostrar' => 'S',
                        'titulo_es' => 'A tu servicio',
                        'titulo_en' => 'At Your Service',
                        'subtitulo_es' => null,
                        'subtitulo_en' => null,
                        'contenido_es' => null,
                        'contenido_en' => null,
                        'boton_texto_es' => null,
                        'boton_texto_en' => null,
                        'boton_url' => null,
                        'clase_css' => null,
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000003',
                        'codigo' => 'portfolio',
                        'orden' => 3,
                        'mostrar' => 'S',
                        'titulo_es' => 'Portafolio',
                        'titulo_en' => 'Portfolio',
                        'subtitulo_es' => null,
                        'subtitulo_en' => null,
                        'contenido_es' => null,
                        'contenido_en' => null,
                        'boton_texto_es' => null,
                        'boton_texto_en' => null,
                        'boton_url' => null,
                        'clase_css' => null,
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000004',
                        'codigo' => 'cta',
                        'orden' => 4,
                        'mostrar' => 'S',
                        'titulo_es' => 'Accede al sistema',
                        'titulo_en' => 'Access the system',
                        'subtitulo_es' => null,
                        'subtitulo_en' => null,
                        'contenido_es' => 'Ingresa al panel administrativo para gestionar el control de asistencias.',
                        'contenido_en' => 'Log in to the admin panel to manage attendance control.',
                        'boton_texto_es' => 'Ingresar',
                        'boton_texto_en' => 'Login',
                        'boton_url' => '/admin/login',
                        'clase_css' => 'bg-dark text-white',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000005',
                        'codigo' => 'contact',
                        'orden' => 5,
                        'mostrar' => 'S',
                        'titulo_es' => 'Contáctanos',
                        'titulo_en' => "Let's Get In Touch!",
                        'subtitulo_es' => null,
                        'subtitulo_en' => null,
                        'contenido_es' => '¿Necesitas ayuda? Contáctanos.',
                        'contenido_en' => 'Need help? Contact us.',
                        'boton_texto_es' => null,
                        'boton_texto_en' => null,
                        'boton_url' => null,
                        'clase_css' => null,
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);
            }
        }

        // Servicios
        if (Schema::hasTable('fr_servicio')) {
            $existsSrv = DB::table('fr_servicio')->whereNull('estado')->exists();
            if (!$existsSrv) {
                DB::table('fr_servicio')->insert([
                    [
                        'id' => '0000000001',
                        'orden' => 1,
                        'icono' => 'fa-calendar-check',
                        'titulo_es' => 'Eventos y turnos',
                        'titulo_en' => 'Events & shifts',
                        'descripcion_es' => 'Gestiona eventos y controla asistencia por persona o departamento.',
                        'descripcion_en' => 'Manage events and track attendance by person or department.',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000002',
                        'orden' => 2,
                        'icono' => 'fa-file-alt',
                        'titulo_es' => 'Reportes',
                        'titulo_en' => 'Reports',
                        'descripcion_es' => 'Exporta PDF y Excel con filtros por fecha y paginación.',
                        'descripcion_en' => 'Export PDF and Excel with date filters and pagination.',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000003',
                        'orden' => 3,
                        'icono' => 'fa-user-check',
                        'titulo_es' => 'Justificaciones',
                        'titulo_en' => 'Justifications',
                        'descripcion_es' => 'Registra y aprueba justificaciones por inasistencia.',
                        'descripcion_en' => 'Register and approve justifications for absence.',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'id' => '0000000004',
                        'orden' => 4,
                        'icono' => 'fa-language',
                        'titulo_es' => 'Multilenguaje',
                        'titulo_en' => 'Multilingual',
                        'descripcion_es' => 'Interfaz en español e inglés con gestión de traducciones.',
                        'descripcion_en' => 'Spanish and English UI with translation management.',
                        'estado' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);
            }
        }

        // Portafolio (por defecto: usa assets si no hay imagen_archivo_id)
        if (Schema::hasTable('fr_portafolio')) {
            $existsPort = DB::table('fr_portafolio')->whereNull('estado')->exists();
            if (!$existsPort) {
                DB::table('fr_portafolio')->insert([
                    ['id' => '0000000001', 'orden' => 1, 'categoria_es' => 'Módulo', 'categoria_en' => 'Module', 'titulo_es' => 'Asistencias', 'titulo_en' => 'Attendance', 'imagen_archivo_id' => null, 'url' => null, 'estado' => null, 'created_at' => $now, 'updated_at' => $now],
                    ['id' => '0000000002', 'orden' => 2, 'categoria_es' => 'Módulo', 'categoria_en' => 'Module', 'titulo_es' => 'Justificaciones', 'titulo_en' => 'Justifications', 'imagen_archivo_id' => null, 'url' => null, 'estado' => null, 'created_at' => $now, 'updated_at' => $now],
                    ['id' => '0000000003', 'orden' => 3, 'categoria_es' => 'Módulo', 'categoria_en' => 'Module', 'titulo_es' => 'Eventos', 'titulo_en' => 'Events', 'imagen_archivo_id' => null, 'url' => null, 'estado' => null, 'created_at' => $now, 'updated_at' => $now],
                    ['id' => '0000000004', 'orden' => 4, 'categoria_es' => 'Módulo', 'categoria_en' => 'Module', 'titulo_es' => 'Departamentos', 'titulo_en' => 'Departments', 'imagen_archivo_id' => null, 'url' => null, 'estado' => null, 'created_at' => $now, 'updated_at' => $now],
                    ['id' => '0000000005', 'orden' => 5, 'categoria_es' => 'Módulo', 'categoria_en' => 'Module', 'titulo_es' => 'Reportes', 'titulo_en' => 'Reports', 'imagen_archivo_id' => null, 'url' => null, 'estado' => null, 'created_at' => $now, 'updated_at' => $now],
                    ['id' => '0000000006', 'orden' => 6, 'categoria_es' => 'Módulo', 'categoria_en' => 'Module', 'titulo_es' => 'Configuración', 'titulo_en' => 'Settings', 'imagen_archivo_id' => null, 'url' => null, 'estado' => null, 'created_at' => $now, 'updated_at' => $now],
                ]);
            }
        }

        // Sincronizar pg_control para evitar colisiones de IDs
        $this->syncControl('FR_PAGINA_INICIO', 1);
        $this->syncControl('FR_MENU', 5);
        $this->syncControl('FR_SECCION', 5);
        $this->syncControl('FR_SERVICIO', 4);
        $this->syncControl('FR_PORTAFOLIO', 6);
    }

    private function syncControl(string $objeto, int $ultimo): void
    {
        try {
            if (!Schema::hasTable('pg_control')) {
                return;
            }
            $g1 = '__';
            $g2 = '______';
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement(
                    'INSERT INTO pg_control(objeto, grupo1, grupo2, ultimo) VALUES (?, ?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE ultimo = GREATEST(ultimo, VALUES(ultimo))',
                    [$objeto, $g1, $g2, $ultimo]
                );
                return;
            }

            if ($driver === 'pgsql') {
                // Asegurar fila
                DB::statement(
                    'INSERT INTO pg_control(objeto, grupo1, grupo2, ultimo) VALUES (?, ?, ?, ?) '
                    . 'ON CONFLICT (objeto, grupo1, grupo2) DO UPDATE SET ultimo = GREATEST(pg_control.ultimo, EXCLUDED.ultimo)',
                    [$objeto, $g1, $g2, $ultimo]
                );
                return;
            }

            // Fallback genérico
            $exists = DB::table('pg_control')->where('objeto', $objeto)->where('grupo1', $g1)->where('grupo2', $g2)->exists();
            if ($exists) {
                DB::table('pg_control')->where('objeto', $objeto)->where('grupo1', $g1)->where('grupo2', $g2)
                    ->update(['ultimo' => $ultimo]);
            } else {
                DB::table('pg_control')->insert(['objeto' => $objeto, 'grupo1' => $g1, 'grupo2' => $g2, 'ultimo' => $ultimo]);
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // No-op
    }
};
