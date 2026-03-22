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
        Schema::create('api_documentation', function (Blueprint $table) {
            $table->increments('id');
            $table->string('method_type');
            $table->string('url');
            $table->string('parameters');
            $table->text('description');
            $table->timestamps();
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_documentation');
    }
};
