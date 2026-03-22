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
        Schema::create('pg_control', function (Blueprint $table) {
            $table->string('objeto', 60);
            $table->string('grupo1', 60)->default('__');
            $table->string('grupo2', 60)->default('______');
            $table->unsignedBigInteger('ultimo')->default(0);

            $table->primary(['objeto', 'grupo1', 'grupo2']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_control');
    }
};
