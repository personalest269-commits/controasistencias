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
        Schema::create('module_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->string('field_name');
            $table->string('field_label');
            $table->integer('field_type');
            $table->integer('field_length')->default(0);
            $table->text('field_options');
            $table->string('related_table');
            $table->string('related_table_field');
            $table->string('related_table_field_display');
            $table->text('validation_rules');
            $table->tinyInteger('show_in_list');
            $table->integer('module_id');
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
        Schema::dropIfExists('module_fields');
    }
};
