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
        Schema::table('pg_departamento', function (Blueprint $table) {
            $table->foreign(['id_jefe'])->references(['id'])->on('pg_persona')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_departamento', function (Blueprint $table) {
            $table->dropForeign('pg_departamento_id_jefe_foreign');
        });
    }
};
