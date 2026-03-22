<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdTipoArchivo extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ad_tipo_archivo')) {
            Schema::drop('ad_tipo_archivo');
        }

        Schema::create('ad_tipo_archivo', function (Blueprint $table) {
            $table->string('codigo', 5)->primary();
            $table->string('descripcion', 255);
            $table->string('tipo_mime', 255);
            $table->string('extension', 10);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_tipo_archivo');
    }
}
