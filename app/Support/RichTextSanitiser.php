<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitises CMS rich text ON SAVE, so the database never holds a payload.
 *
 * Filament registers its own HtmlSanitizerConfig and sanitises on RENDER
 * (see vendor/filament/support/src/SupportServiceProvider.php). That protects
 * Filament's own output, but leaves whatever was pasted sitting in the
 * database and depends on every future render path remembering to clean it.
 * The public site renders this HTML unescaped, so we clean it before storage.
 *
 * Allowlist, not blocklist: blocklists lose to novel payloads.
 */
class RichTextSanitiser
{
    public static function sanitise(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return (new HtmlSanitizer(self::config()))->sanitize($html);
    }

    private static function config(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowAttribute('class', allowedElements: '*');
    }
}
