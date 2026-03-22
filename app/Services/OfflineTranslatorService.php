<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

/**
 * Traducción offline opcional.
 *
 * Si Argos Translate está instalado en el servidor (python3 + argostranslate + paquetes ES/EN),
 * permite traducir sin usar APIs pagas.
 *
 * Si no está instalado, el sistema debe permitir traducción manual.
 */
class OfflineTranslatorService
{
    public static function isArgosInstalled(): bool
    {
        return Cache::remember('argos_translate_installed', 3600, function () {
            try {
                $p = new Process(['python3', '-c', 'import argostranslate']);
                $p->setTimeout(3);
                $p->run();
                return $p->isSuccessful();
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

    /**
     * Traduce un texto con un script python local.
     */
    public static function translate(string $text, string $from = 'es', string $to = 'en'): ?string
    {
        if (!self::isArgosInstalled()) {
            return null;
        }
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        try {
            $script = base_path('scripts/argos_translate.py');
            $p = new Process(['python3', $script, $from, $to, $text]);
            $p->setTimeout(10);
            $p->run();
            if (!$p->isSuccessful()) {
                return null;
            }
            return trim((string) $p->getOutput());
        } catch (\Throwable $e) {
            return null;
        }
    }
}
