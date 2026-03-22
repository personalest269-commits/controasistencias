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
        Schema::create('email_plantillas', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('slug')->unique('email_templates_slug_unique');
            $table->string('name');
            $table->string('from_name')->nullable();
            $table->json('variables')->nullable();
            $table->timestamps();
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_plantillas');
    }
};
