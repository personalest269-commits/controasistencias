<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    /**
     * NOTE: Laravel's base Mailable already defines a property named "$html".
     * Redeclaring it (especially with a type) triggers a PHP fatal error.
     */
    public string $htmlContent;
    public ?string $fromName;

    public function __construct(string $subjectLine, string $htmlContent, ?string $fromName = null)
    {
        $this->subjectLine = $subjectLine;
        $this->htmlContent = $htmlContent;
        $this->fromName = $fromName;
    }

    public function build()
    {
        $mail = $this->subject($this->subjectLine)
            ->view('emails.generic', ['html' => $this->htmlContent]);

        // Keep address from system settings, but allow overriding the name.
        if ($this->fromName) {
            $mail->from(config('mail.from.address'), $this->fromName);
        }

        return $mail;
    }
}
