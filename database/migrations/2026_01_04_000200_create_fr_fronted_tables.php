<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parametrización del frontend (template creative) desde base de datos.
 *
 * Tablas:
 *  - fr_pagina_inicio
 *  - fr_menu
 *  - fr_seccion
 *  - fr_servicio
 *  - fr_portafolio
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------
        // FR_PAGINA_INICIO
        // ---------------------------
        if (!Schema::hasTable('fr_pagina_inicio')) {
            Schema::create('fr_pagina_inicio', function (Blueprint $table) {
                $table->string('id', 10)->primary();

                // Branding
                $table->string('nombre_sitio_es', 200)->nullable();
                $table->string('nombre_sitio_en', 200)->nullable();
                $table->string('logo_archivo_id', 20)->nullable()->index(); // ad_archivo_digital.id

                // Hero
                $table->string('hero_titulo_es', 255)->nullable();
                $table->string('hero_titulo_en', 255)->nullable();
                $table->text('hero_subtitulo_es')->nullable();
                $table->text('hero_subtitulo_en')->nullable();
                $table->string('hero_boton_texto_es', 120)->nullable();
                $table->string('hero_boton_texto_en', 120)->nullable();
                $table->string('hero_boton_url', 600)->nullable();
                $table->string('hero_fondo_archivo_id', 20)->nullable()->index(); // ad_archivo_digital.id

                // Contacto
                $table->string('contacto_telefono', 120)->nullable();
                $table->string('contacto_email', 255)->nullable();
                $table->string('contacto_direccion_es', 255)->nullable();
                $table->string('contacto_direccion_en', 255)->nullable();

                // Cookies
                $table->char('cookies_activo', 1)->nullable()->default('N');
                $table->text('cookies_texto_es')->nullable();
                $table->text('cookies_texto_en')->nullable();
                $table->string('cookies_btn_aceptar_es', 80)->nullable();
                $table->string('cookies_btn_aceptar_en', 80)->nullable();
                $table->string('cookies_btn_rechazar_es', 80)->nullable();
                $table->string('cookies_btn_rechazar_en', 80)->nullable();

                // Eliminación lógica
                $table->char('estado', 1)->nullable()->default(null)->index();
                $table->timestamps();
            });
        }

        // ---------------------------
        // FR_MENU
        // ---------------------------
        if (!Schema::hasTable('fr_menu')) {
            Schema::create('fr_menu', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->integer('orden')->default(1)->index();

                // Texto
                $table->string('texto_es', 120);
                $table->string('texto_en', 120)->nullable();

                // anchor|route|url
                $table->string('tipo', 20)->default('anchor');
                $table->string('destino', 600)->nullable();
                $table->char('nuevo_tab', 1)->nullable()->default('N');

                $table->char('estado', 1)->nullable()->default(null)->index();
                $table->timestamps();
            });
        }

        // ---------------------------
        // FR_SECCION
        // ---------------------------
        if (!Schema::hasTable('fr_seccion')) {
            Schema::create('fr_seccion', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('codigo', 50)->index(); // about/services/portfolio/cta/contact
                $table->integer('orden')->default(1)->index();
                $table->char('mostrar', 1)->nullable()->default('S');

                $table->string('titulo_es', 255)->nullable();
                $table->string('titulo_en', 255)->nullable();
                $table->text('subtitulo_es')->nullable();
                $table->text('subtitulo_en')->nullable();
                $table->longText('contenido_es')->nullable();
                $table->longText('contenido_en')->nullable();
                $table->string('boton_texto_es', 120)->nullable();
                $table->string('boton_texto_en', 120)->nullable();
                $table->string('boton_url', 600)->nullable();
                $table->string('clase_css', 255)->nullable();

                $table->char('estado', 1)->nullable()->default(null)->index();
                $table->timestamps();
            });
        }

        // ---------------------------
        // FR_SERVICIO
        // ---------------------------
        if (!Schema::hasTable('fr_servicio')) {
            Schema::create('fr_servicio', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->integer('orden')->default(1)->index();

                $table->string('icono', 80)->nullable(); // clase FA, ej: fa-gem
                $table->string('titulo_es', 200);
                $table->string('titulo_en', 200)->nullable();
                $table->text('descripcion_es')->nullable();
                $table->text('descripcion_en')->nullable();

                $table->char('estado', 1)->nullable()->default(null)->index();
                $table->timestamps();
            });
        }

        // ---------------------------
        // FR_PORTAFOLIO
        // ---------------------------
        if (!Schema::hasTable('fr_portafolio')) {
            Schema::create('fr_portafolio', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->integer('orden')->default(1)->index();

                $table->string('titulo_es', 200)->nullable();
                $table->string('titulo_en', 200)->nullable();
                $table->string('categoria_es', 200)->nullable();
                $table->string('categoria_en', 200)->nullable();

                $table->string('imagen_archivo_id', 20)->nullable()->index(); // ad_archivo_digital.id
                $table->string('url', 600)->nullable();

                $table->char('estado', 1)->nullable()->default(null)->index();
                $table->timestamps();
            });
        }

        // ---------------------------
        // Triggers IDs (MySQL/MariaDB)
        // ---------------------------
        if (DB::getDriverName() === 'mysql') {
            $this->createTrigger('fr_pagina_inicio', 'FR_PAGINA_INICIO');
            $this->createTrigger('fr_menu', 'FR_MENU');
            $this->createTrigger('fr_seccion', 'FR_SECCION');
            $this->createTrigger('fr_servicio', 'FR_SERVICIO');
            $this->createTrigger('fr_portafolio', 'FR_PORTAFOLIO');
        }
    }

    private function createTrigger(string $table, string $objeto): void
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS tr_' . $table . '_bi');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::unprepared(<<<SQL
CREATE TRIGGER tr_{$table}_bi
BEFORE INSERT ON {$table}
FOR EACH ROW
BEGIN
    DECLARE v_valor BIGINT;
    IF NEW.id IS NULL OR TRIM(NEW.id) = '' THEN
        CALL sp_f_ultimo('{$objeto}', NULL, NULL, v_valor);
        SET NEW.id = LPAD(v_valor, 10, '0');
    END IF;
END
SQL);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // No se hace rollback por seguridad (pueden existir datos)
    }
};
