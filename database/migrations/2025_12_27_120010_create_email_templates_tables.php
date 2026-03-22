<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('from_name')->nullable();
            $table->json('variables')->nullable();
            $table->timestamps();
        });

        Schema::create('email_template_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('subject');
            $table->longText('body');
            $table->timestamps();
            $table->unique(['email_template_id', 'locale']);
        });

        // Seed defaults (English / Spanish)
        $newUserId = DB::table('email_templates')->insertGetId([
            'slug' => 'new_user',
            'name' => 'New User',
            'from_name' => 'Support',
            'variables' => json_encode(['app_name', 'company_name', 'email', 'password', 'app_url']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resetId = DB::table('email_templates')->insertGetId([
            'slug' => 'reset_password',
            'name' => 'Reset Password',
            'from_name' => 'Support',
            'variables' => json_encode(['app_name', 'company_name', 'email', 'reset_link', 'expire_minutes']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // New User
        DB::table('email_template_translations')->insert([
            [
                'email_template_id' => $newUserId,
                'locale' => 'en',
                'subject' => 'New User',
                'body' => "Hello,<br><br>Welcome to {app_name}.<br><br>Email: {email}<br>Password: {password}<br><br>Login here: {app_url}<br><br>Thanks,<br>{app_name}",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email_template_id' => $newUserId,
                'locale' => 'es',
                'subject' => 'Nuevo usuario',
                'body' => "Hola,<br><br>Bienvenido/a a {app_name}.<br><br>Email: {email}<br>Contrase&ntilde;a: {password}<br><br>Ingresa aqu&iacute;: {app_url}<br><br>Gracias,<br>{app_name}",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Reset Password
        DB::table('email_template_translations')->insert([
            [
                'email_template_id' => $resetId,
                'locale' => 'en',
                'subject' => 'Reset Password',
                'body' => "Hello,<br><br>You are receiving this email because we received a password reset request for your account.<br><br>Reset link: {reset_link}<br><br>This link will expire in {expire_minutes} minutes.<br><br>If you did not request a password reset, no further action is required.<br><br>Thanks,<br>{app_name}",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email_template_id' => $resetId,
                'locale' => 'es',
                'subject' => 'Restablecer contraseña',
                'body' => "Hola,<br><br>Recibiste este correo porque se solicit&oacute; un restablecimiento de contrase&ntilde;a para tu cuenta.<br><br>Enlace: {reset_link}<br><br>Este enlace caduca en {expire_minutes} minutos.<br><br>Si no lo solicitaste, ignora este correo.<br><br>Gracias,<br>{app_name}",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_translations');
        Schema::dropIfExists('email_templates');
    }
};
