<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Schema;

class EmailTemplateRenderer
{
    /**
     * Render a template by slug for a locale.
     *
     * @return array{subject: string, body: string, from_name: ?string}
     */
    public function render(string $slug, string $locale, array $vars = []): array
    {
        if (!Schema::hasTable('email_plantillas') || !Schema::hasTable('email_plantillas_traduccion')) {
            return ['subject' => '', 'body' => '', 'from_name' => null];
        }

        $template = EmailTemplate::with('translations.idioma')->where('slug', $slug)->first();
        if (!$template) {
            return ['subject' => '', 'body' => '', 'from_name' => null];
        }

        $translation = $template->translationFor($locale);
        if (!$translation) {
            return ['subject' => '', 'body' => '', 'from_name' => $template->from_name];
        }

        $subject = $this->replace($translation->subject, $vars);
        $body = $this->replace($translation->body, $vars);

        return [
            'subject' => $subject,
            'body' => $body,
            'from_name' => $template->from_name,
        ];
    }

    private function replace(string $text, array $vars): string
    {
        // Accept {key} placeholders
        foreach ($vars as $k => $v) {
            $text = str_replace('{' . $k . '}', (string) $v, $text);
        }
        return $text;
    }
}
