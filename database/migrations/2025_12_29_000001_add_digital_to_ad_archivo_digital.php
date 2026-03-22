<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDigitalToAdArchivoDigital extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('ad_archivo_digital')) {
            return;
        }

        Schema::table('ad_archivo_digital', function (Blueprint $table) {
            if (!Schema::hasColumn('ad_archivo_digital', 'digital')) {
                // Contenido cifrado del archivo (base64 cifrado con Crypt)
                $table->longText('digital')->nullable()->after('ruta');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('ad_archivo_digital')) {
            return;
        }

        Schema::table('ad_archivo_digital', function (Blueprint $table) {
            if (Schema::hasColumn('ad_archivo_digital', 'digital')) {
                $table->dropColumn('digital');
            }
        });
    }
}
