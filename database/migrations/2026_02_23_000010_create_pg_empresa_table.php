<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePgEmpresaTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pg_empresa')) {
            return;
        }

        Schema::create('pg_empresa', function (Blueprint $table) {
            $table->string('id', 10)->primary(); // ID lo genera trigger
            $table->string('nombre', 255);
            $table->string('ruc', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 100)->nullable();

            // NULL activo, 'X' eliminado lógico
            $table->string('estado', 1)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pg_empresa');
    }
}
