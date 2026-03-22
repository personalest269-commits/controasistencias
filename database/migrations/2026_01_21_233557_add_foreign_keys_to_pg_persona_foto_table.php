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
        Schema::table('pg_persona_foto', function (Blueprint $table) {
            $table->foreign(['id_archivo'])->references(['id'])->on('ad_archivo_digital')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_persona_foto', function (Blueprint $table) {
            $table->dropForeign('pg_persona_foto_id_archivo_foreign');
        });
    }
};
