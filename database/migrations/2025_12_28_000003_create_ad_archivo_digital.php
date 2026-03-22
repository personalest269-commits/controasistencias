<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdArchivoDigital extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ad_archivo_digital')) {
            Schema::drop('ad_archivo_digital');
        }

        Schema::create('ad_archivo_digital', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('tipo_documento_codigo', 5)->nullable();
            $table->string('tipo_archivo_codigo', 5)->nullable();

            $table->string('nombre_original', 255);
            $table->string('ruta', 600);
            $table->string('tipo_mime', 255);
            $table->string('extension', 10);
            $table->integer('tamano');
            $table->string('descripcion', 255)->nullable();

            // null = activo, 'X' = eliminado lógico
            $table->char('estado', 1)->nullable()->default(null);

            $table->timestamps();

            $table->index(['estado']);
            $table->index(['tipo_documento_codigo']);
            $table->index(['tipo_archivo_codigo']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_archivo_digital');
    }
}
