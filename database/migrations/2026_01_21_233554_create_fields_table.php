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
        Schema::create('fields', function (Blueprint $table) {
            $table->increments('id');
            $table->string('field_name');
            $table->string('field_text');
            $table->tinyInteger('status');
            $table->text('validation_rules')->nullable();
            $table->timestamps();
            $table->softDeletes()->nullable(false)->useCurrent();
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
