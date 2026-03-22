<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pg_permisos_role', function (Blueprint $table) {
            $table->string('permission_id', 10);
            $table->string('role_id', 10)->index('permission_role_role_id_foreign');
            $table->char('estado', 1)->nullable()->index();

            $table->primary(['permission_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_permisos_role');
    }
};
