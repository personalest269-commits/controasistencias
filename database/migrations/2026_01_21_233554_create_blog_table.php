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
        Schema::create('blog', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('content');
            $table->string('meta_tags');
            $table->text('meta_description');
            $table->string('slug');
            $table->text('excerpt');
            $table->integer('category');
            $table->string('tags');
            $table->string('author_name');
            $table->integer('status');
            $table->string('image');
            $table->timestamps();
            $table->softDeletes();
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog');
    }
};
