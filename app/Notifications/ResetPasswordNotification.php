<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\EmailTemplateRenderer;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        // If an editable template exists in DB, use it (otherwise fall back to language files).
        $renderer = app(EmailTemplateRenderer::class);
        $rendered = $renderer->render('reset_password', app()->getLocale(), [
            'app_name' => config('app.name'),
            'company_name' => config('app.name'),
            'email' => $notifiable->getEmailForPasswordReset(),
            'reset_link' => '<a href="' . e($resetUrl) . '">' . e($resetUrl) . '</a>',
            'expire_minutes' => $expire,
        ]);

        if (!empty($rendered['subject']) && !empty($rendered['body'])) {
            $mail = (new MailMessage)
                ->subject($rendered['subject'])
                ->view('emails.generic', ['html' => $rendered['body']]);

            // Allow templates to override only the sender display name.
            if (!empty($rendered['from_name'])) {
                $mail->from(config('mail.from.address'), $rendered['from_name']);
            }

            return $mail;
        }

        return (new MailMessage)
            ->subject(__('password_reset.mail.subject'))
            ->greeting(__('password_reset.mail.greeting', [
                'name' => $notifiable->name ?? $notifiable->email,
            ]))
            ->line(__('password_reset.mail.line1'))
            ->action(__('password_reset.mail.action'), $resetUrl)
            ->line(__('password_reset.mail.line2', ['count' => $expire]))
            ->line(__('password_reset.mail.line3'));
    }
}
