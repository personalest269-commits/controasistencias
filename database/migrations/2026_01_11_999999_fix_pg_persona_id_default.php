<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('pg_persona', function (Blueprint $table) {
            $table->string('id', 10)->default('')->change();
        });
    }
    public function down() {}
};
