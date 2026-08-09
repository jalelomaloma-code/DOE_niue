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

it('keeps legitimate formatting and links', function () {
    $clean = RichTextSanitiser::sanitise(
        '<h2>Waste</h2><p><strong>Bold</strong> and <em>italic</em></p>'
        .'<ul><li>One</li></ul><a href="/waste-and-recycling">Local</a>'
        .'<a href="https://gov.nu">External</a>'
    );

    expect($clean)->toContain('<h2>')
        ->and($clean)->toContain('<strong>')
        ->and($clean)->toContain('<li>')
        ->and($clean)->toContain('/waste-and-recycling')
        ->and($clean)->toContain('https://gov.nu');
});

it('returns null unchanged', function () {
    expect(RichTextSanitiser::sanitise(null))->toBeNull();
});
