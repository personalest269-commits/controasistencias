<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pg_persona_stg', function (Blueprint $table) {
            if (!Schema::hasColumn('pg_persona_stg', 'empresa_id')) {
                $table->string('empresa_id', 10)->nullable()->after('batch_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pg_persona_stg', function (Blueprint $table) {
            if (Schema::hasColumn('pg_persona_stg', 'empresa_id')) {
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }
};
