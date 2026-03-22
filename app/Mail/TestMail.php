<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $appName;

    public function __construct(string $appName)
    {
        $this->appName = $appName;
    }

    public function build()
    {
        $html = "<p>This is a test email from <strong>{$this->appName}</strong>.</p><p>If you received this message, your SMTP settings are working.</p>";

        return $this->subject('Test Mail')
            ->view('emails.generic', ['html' => $html]);
    }
}
