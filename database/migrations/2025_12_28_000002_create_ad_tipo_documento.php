<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdTipoDocumento extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ad_tipo_documento')) {
            Schema::drop('ad_tipo_documento');
        }

        Schema::create('ad_tipo_documento', function (Blueprint $table) {
            $table->string('codigo', 5)->primary();
            $table->string('descripcion', 255);
            $table->integer('tamano_maximo')->default(10000);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_tipo_documento');
    }
}
