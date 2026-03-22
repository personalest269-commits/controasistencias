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
        Schema::create('pg_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('level', 20)->default('error')->index();
            $table->string('channel', 50)->nullable();
            $table->text('message');
            $table->string('exception_class', 255)->nullable();
            $table->string('exception_code', 100)->nullable();
            $table->string('file', 255)->nullable();
            $table->integer('line')->nullable();
            $table->longText('trace')->nullable();
            $table->json('context')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('usuario_id', 10)->nullable()->index();
            $table->char('estado', 1)->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by', 10)->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_log');
    }
};
