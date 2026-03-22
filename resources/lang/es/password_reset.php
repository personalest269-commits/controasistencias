<?php

return [
    // Web UI
    'title' => 'Restablecer contraseña',
    'forgot_title' => '¿Olvidaste tu contraseña?',
    'forgot_subtitle' => 'Ingresa tu correo y te enviaremos un enlace para restablecerla.',
    'reset_subtitle' => 'Define tu nueva contraseña.',

    'email' => 'Correo electrónico',
    'password' => 'Contraseña',
    'confirm_password' => 'Confirmar contraseña',

    'send_link' => 'Enviar enlace de restablecimiento',
    'reset_button' => 'Restablecer contraseña',
    'back_to_login' => 'Volver al inicio de sesión',

    // Mail (ResetPasswordNotification)
    'mail' => [
        'subject' => 'Restablece tu contraseña',
        'greeting' => 'Hola :name,',
        'line1' => 'Recibiste este correo porque se solicitó restablecer la contraseña de tu cuenta.',
        'action' => 'Restablecer contraseña',
        'line2' => 'Este enlace para restablecer la contraseña expirará en :count minutos.',
        'line3' => 'Si no solicitaste este cambio, puedes ignorar este correo.',
    ],

    // API helper messages
    'api' => [
        'request_form' => 'Envía una petición POST a /password/email con el correo para recibir el enlace de restablecimiento.',
        'reset_form' => 'Envía una petición POST a /password/reset con token, email, password y password_confirmation.',
    ],

    'errors' => [
        'send_failed' => 'No se pudo enviar el correo de restablecimiento. Revisa la configuración de correo (Email Settings) y vuelve a intentar.',
    ],
];
