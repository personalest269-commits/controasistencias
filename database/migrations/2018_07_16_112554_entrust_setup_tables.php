<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EntrustSetupTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        // Create table for storing roles
        Schema::create('roles', function (Blueprint $table) {
            // IDs como string 10 dígitos (0000000001)
            $table->string('id', 10)->primary();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->char('estado', 1)->nullable()->index();
        });

        // Create table for associating roles to users (Many-to-Many)
        Schema::create('role_user', function (Blueprint $table) {
            $table->string('usuario_id', 10);
            $table->string('role_id', 10);

            $table->foreign('usuario_id')->references('id')->on('pg_usuario')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['usuario_id', 'role_id']);
        });

        // Create table for storing permissions
        Schema::create('pg_permisos', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->char('estado', 1)->nullable()->index();
        });

        // Create table for associating permissions to roles (Many-to-Many)
        Schema::create('pg_permisos_role', function (Blueprint $table) {
            $table->string('permission_id', 10);
            $table->string('role_id', 10);
            $table->char('estado', 1)->nullable()->index();

            $table->foreign('permission_id')->references('id')->on('pg_permisos')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists('pg_permisos_role');
        Schema::dropIfExists('pg_permisos');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
}
