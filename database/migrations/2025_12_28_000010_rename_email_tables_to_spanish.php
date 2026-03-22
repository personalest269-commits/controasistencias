<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Rename tables created by previous versions of the project.
        if (Schema::hasTable('email_settings') && !Schema::hasTable('email_configuraciones')) {
            Schema::rename('email_settings', 'email_configuraciones');
        }

        if (Schema::hasTable('email_templates') && !Schema::hasTable('email_plantillas')) {
            Schema::rename('email_templates', 'email_plantillas');
        }

        // Standardize to the correct Spanish table name: "email_plantillas_traduccion".
        // Older versions had a typo: "email_plantillas_tranduccion".
        if (Schema::hasTable('email_plantillas_tranduccion') && !Schema::hasTable('email_plantillas_traduccion')) {
            Schema::rename('email_plantillas_tranduccion', 'email_plantillas_traduccion');
        }

        if (Schema::hasTable('email_template_translations') && !Schema::hasTable('email_plantillas_traduccion')) {
            Schema::rename('email_template_translations', 'email_plantillas_traduccion');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_configuraciones') && !Schema::hasTable('email_settings')) {
            Schema::rename('email_configuraciones', 'email_settings');
        }

        if (Schema::hasTable('email_plantillas') && !Schema::hasTable('email_templates')) {
            Schema::rename('email_plantillas', 'email_templates');
        }

        if (Schema::hasTable('email_plantillas_traduccion') && !Schema::hasTable('email_template_translations')) {
            Schema::rename('email_plantillas_traduccion', 'email_template_translations');
        }
    }
};
