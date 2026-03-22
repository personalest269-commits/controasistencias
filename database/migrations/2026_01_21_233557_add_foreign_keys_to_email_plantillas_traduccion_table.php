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
        Schema::table('email_plantillas_traduccion', function (Blueprint $table) {
            $table->foreign(['email_template_id'])->references(['id'])->on('email_plantillas')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['idioma_id'], 'email_plantillas_traduccion_idioma_id_fk')->references(['id'])->on('pg_idiomas')->onUpdate('cascade')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_plantillas_traduccion', function (Blueprint $table) {
            $table->dropForeign('email_plantillas_traduccion_email_template_id_foreign');
            $table->dropForeign('email_plantillas_traduccion_idioma_id_fk');
        });
    }
};
