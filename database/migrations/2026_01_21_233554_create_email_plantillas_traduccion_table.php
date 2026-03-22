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
        Schema::create('email_plantillas_traduccion', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('email_template_id', 10);
            $table->string('idioma_id', 10)->index();
            $table->string('subject');
            $table->longText('body');
            $table->timestamps();
            $table->char('estado', 1)->nullable()->index();

            $table->unique(['email_template_id', 'idioma_id'], 'email_plantillas_traduccion_template_idioma_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_plantillas_traduccion');
    }
};
