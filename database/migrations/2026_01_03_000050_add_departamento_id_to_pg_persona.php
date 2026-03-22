<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_persona')) {
            return;
        }

        Schema::table('pg_persona', function (Blueprint $table) {
            if (!Schema::hasColumn('pg_persona', 'departamento_id')) {
                // ID VARCHAR(10) como el resto del sistema
                $table->string('departamento_id', 10)->nullable()->after('email');
                $table->index('departamento_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pg_persona')) {
            return;
        }

        Schema::table('pg_persona', function (Blueprint $table) {
            if (Schema::hasColumn('pg_persona', 'departamento_id')) {
                $table->dropIndex(['departamento_id']);
                $table->dropColumn('departamento_id');
            }
        });
    }
};
