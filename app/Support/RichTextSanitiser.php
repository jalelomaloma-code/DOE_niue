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
 *
 * Heading levels are policed here, not just in the editor toolbar. The page
 * template reserves `<h1>` for the page title and `<h2>` for a block's own
 * heading (see resources/views/components/blocks/rich-text.blade.php), so
 * rich-text body content must start no higher than `<h3>`. A restricted
 * toolbar only stops an editor from *clicking* a heading level — pasting
 * from Word or another CMS carries `<h1>`/`<h2>` tags that never touch the
 * toolbar. This is the one path every save goes through, so it's the one
 * that has to hold the line. `blockElement()`, not `dropElement()`: an
 * unwrapped heading keeps its text and renders as a paragraph, a visible
 * signal to the editor to reformat, rather than silently deleting whatever
 * they wrote.
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
            // No allowAttribute() call at all, so `href` on links (granted by
            // allowSafeElements()) is the only attribute that survives. In
            // particular `class` is NOT allowed: the utility stylesheet is
            // global, so `class="hidden"` would let an editor make content
            // invisible on the rendered page while it still reads as present
            // in the CMS, and `class="fixed inset-0 z-50"` would let a body
            // paragraph cover the whole page. The `prose` wrapper in
            // rich-text.blade.php does all the styling the block set needs —
            // no partial and no seeded content supplies its own classes.
            // <h1> is the page title, <h2> is a block's own heading — body
            // content starts at <h3>. blockElement() unwraps the tag and
            // keeps the text rather than deleting it (dropElement()), so a
            // pasted heading survives as a visible, reformattable paragraph.
            ->blockElement('h1')
            ->blockElement('h2');
    }
}
