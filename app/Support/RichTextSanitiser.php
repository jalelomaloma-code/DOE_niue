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
 *
 * `allowSafeElements()` is broader than "headings, paragraphs, lists,
 * strong/em, blockquote, a" — it is every element the W3C Sanitizer API
 * reference marks safe, which also includes img, video, audio, button,
 * canvas, table and details/summary, among others. `<script>`, `<iframe>`,
 * `<object>` and `<svg>` aren't in that reference at all, so no config here
 * can reach them. Elements that ARE in the reference but marked unsafe —
 * form, input, select, textarea — are excluded; `style` and event-handler
 * (`on*`) attributes are excluded too. Before adding an `allowElement()` call
 * for Task 6's block set, check whether `allowSafeElements()` already covers
 * it — see vendor/symfony/html-sanitizer/Reference/W3CReference.php.
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
        // Relative media URLs (e.g. an <img src="/images/x.jpg">) are NOT
        // allowed here — allowRelativeMedias() is never called, unlike
        // Filament's own render-time config. Any relative image/audio/video
        // source is silently stripped. Task 6 will hit this the moment
        // images are wired into the rich-text field; call
        // ->allowRelativeMedias() then if relative image paths are needed.
        return (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowAttribute('class', allowedElements: '*');
    }
}
