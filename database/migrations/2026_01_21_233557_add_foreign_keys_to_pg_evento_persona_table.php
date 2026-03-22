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
        Schema::table('pg_evento_persona', function (Blueprint $table) {
            $table->foreign(['evento_id'])->references(['id'])->on('pg_eventos')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['persona_id'])->references(['id'])->on('pg_persona')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pg_evento_persona', function (Blueprint $table) {
            $table->dropForeign('pg_evento_persona_evento_id_foreign');
            $table->dropForeign('pg_evento_persona_persona_id_foreign');
        });
    }
};
