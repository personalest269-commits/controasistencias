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
        Schema::create('menus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('permission_name', 255)->nullable();
            $table->string('url', 256);
            $table->string('icon', 50)->default('fa-cube');
            $table->string('type', 20)->default('module');
            $table->unsignedInteger('parent')->default(0);
            $table->unsignedInteger('hierarchy')->default(0);
            $table->unsignedInteger('module_id')->default(0);
            $table->timestamps();
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
