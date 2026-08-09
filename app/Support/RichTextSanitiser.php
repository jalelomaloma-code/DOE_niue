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
 * form, input, select, textarea — are excluded. Before adding an
 * `allowElement()` call for Task 6's block set, check whether
 * `allowSafeElements()` already covers it — see
 * vendor/symfony/html-sanitizer/Reference/W3CReference.php.
 *
 * `allowSafeElements()` is equally broad about ATTRIBUTES, which is easy to
 * miss: it grants every attribute W3CReference::ATTRIBUTES marks `true` — some
 * 250 of them — on every one of those elements. Only `class`, `style`,
 * `hidden`, `contenteditable` and the `on*` handlers are marked unsafe there.
 * `id`, `title`, `target`, `src`, `alt`, `rel`, `lang` and `face` are all
 * granted. That is why config() below re-declares every allowed element with
 * an empty attribute list; see the comment there.
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
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowLinkSchemes(['http', 'https', 'mailto']);

        // Spec section 6: "strips every attribute except `href` on links".
        //
        // Omitting allowAttribute() does NOT achieve that — it is not an
        // opt-in switch. allowSafeElements() has already granted ~250
        // attributes on every element it allowed, so the policy has to be
        // imposed by re-declaring each of those elements with an empty
        // attribute list. Two of the survivors were live problems on this
        // site:
        //   * `id` — the layout gives <main> id="main" and the skip link
        //     targets #main (public.blade.php); the header owns
        //     #nav-disclosure, #menu-toggle and #mobile-nav. Editor body
        //     content carrying id="main" hijacks "Skip to main content" and
        //     duplicates ids that aria-labelledby wiring depends on.
        //   * `src` — allowRelativeMedias() is never called, but
        //     allowedMediaSchemes defaults to http/https/data, so an absolute
        //     <img src="https://…"> came straight through: a third-party
        //     request issued by a government page, and an image with no
        //     enforced alt text, when PageForm's `image` block and the
        //     featured-image uploads both make alt required.
        // `class` matters for the same reason it was removed in the first
        // place: the utility stylesheet is global, so `class="hidden"` would
        // let an editor make content invisible on the rendered page while it
        // still reads as present in the CMS, and `class="fixed inset-0 z-50"`
        // would let a body paragraph cover the whole page. The `prose` wrapper
        // in rich-text.blade.php does all the styling the block set needs — no
        // partial and no seeded content supplies its own classes.
        //
        // Attributes are narrowed, ELEMENTS are deliberately not. The
        // library's default action for an unconfigured element is Drop, which
        // deletes the element's text along with its tag (see
        // Visitor\DomVisitor::enterNode()). Declaring only the spec's block
        // set — headings, paragraphs, lists, bold, italic, links, blockquote —
        // would silently delete the words inside every wrapper Word and the
        // editor also emit: <div>, <span>, <font>, <u>, <s>, <sub>, <sup>,
        // <pre>/<code>, tables. Filament's default toolbar
        // (RichEditor::getDefaultToolbarButtons()) can produce most of those
        // today. Keeping allowSafeElements()' element breadth and stripping
        // attributes reaches the spec's stated policy with no formatting
        // collateral; RichTextSanitiserTest pins both halves.
        foreach (array_keys($config->getAllowedElements()) as $element) {
            $config = $config->allowElement($element, []);
        }

        return $config
            // The one exception the spec names. Scheme filtering still applies:
            // UrlAttributeSanitizer is registered by the config constructor and
            // is independent of this grant, so allowLinkSchemes() above still
            // drops `javascript:` and `data:` hrefs.
            ->allowElement('a', ['href'])
            // With no attributes left to carry, a media element can only ever
            // render as an empty tag — `<img />` with no src is dead markup
            // that screen readers may still announce. Dropped rather than kept
            // inert. Images on this site come from PageForm's `image` block and
            // the featured-image uploads, both of which require alt text; there
            // is no image path through pasted rich text and there is not meant
            // to be one.
            ->dropElement('img')
            ->dropElement('audio')
            ->dropElement('video')
            // <h1> is the page title, <h2> is a block's own heading — body
            // content starts at <h3>. blockElement() unwraps the tag and
            // keeps the text rather than deleting it (dropElement()), so a
            // pasted heading survives as a visible, reformattable paragraph.
            ->blockElement('h1')
            ->blockElement('h2');
    }
}
