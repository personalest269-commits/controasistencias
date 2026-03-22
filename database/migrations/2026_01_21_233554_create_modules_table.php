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
        Schema::create('modules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('module_name');
            $table->string('module_icon');
            $table->string('module_table_name')->nullable();
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('modules');
    }
};
