<?php

return [
    // Web UI
    'title' => 'Reset Password',
    'forgot_title' => 'Forgot Password',
    'forgot_subtitle' => 'Enter your email and we will send you a reset link.',
    'reset_subtitle' => 'Set your new password.',

    'email' => 'E-mail Address',
    'password' => 'Password',
    'confirm_password' => 'Confirm Password',

    'send_link' => 'Send Password Reset Link',
    'reset_button' => 'Reset Password',
    'back_to_login' => 'Back to login',

    // Mail (ResetPasswordNotification)
    'mail' => [
        'subject' => 'Reset your password',
        'greeting' => 'Hello :name,',
        'line1' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'line2' => 'This password reset link will expire in :count minutes.',
        'line3' => 'If you did not request a password reset, no further action is required.',
    ],

    // API helper messages
    'api' => [
        'request_form' => 'Send a POST request to /password/email with an email to receive a reset link.',
        'reset_form' => 'Send a POST request to /password/reset with token, email, password and password_confirmation.',
    ],

    'errors' => [
        'send_failed' => 'Could not send the reset email. Please verify your SMTP configuration in Account Settings → Email Settings.',
    ],
];
