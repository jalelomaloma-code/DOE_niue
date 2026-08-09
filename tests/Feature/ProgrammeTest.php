<?php

use App\Enums\ContentStatus;
use App\Models\Programme;

it('filters featured programmes', function () {
    Programme::factory()->count(3)->create();
    Programme::factory()->create(['is_featured' => true, 'title' => 'Marine Conservation']);

    expect(Programme::published()->featured()->count())->toBe(1);
});

it('falls back to the summary for the SEO description', function () {
    $programme = Programme::factory()->create([
        'summary' => 'Protecting Niue reef systems.',
        'seo_description' => null,
    ]);

    expect($programme->seoDescription())->toBe('Protecting Niue reef systems.');
});

it('reports isPublished() as true on a freshly loaded record, not just the in-memory instance', function () {
    $programme = Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    // Re-fetch from the database rather than reusing the in-memory instance:
    // the enum cast is what makes the DB's raw 'published' string compare
    // correctly against ContentStatus::Published in isPublished().
    $fresh = Programme::findOrFail($programme->id);

    expect($fresh->isPublished())->toBeTrue();
});

/*
 * Sanitisation lives on the model, not on ProgrammeForm, so it also covers
 * seeders, tinker and any future non-Filament writer. This test writes
 * straight through the factory -- no Filament, no Tiptap -- so BOTH
 * assertions below are load-bearing: nothing upstream of the mutator
 * touches either tag on this path.
 */
it('strips a script tag and unwraps a pasted h1 from the body on save', function () {
    $programme = Programme::factory()->create([
        'body' => '<h1>Pasted Heading</h1><p>Safe</p><script>alert(1)</script>',
    ]);

    $stored = Programme::findOrFail($programme->id)->body;

    expect($stored)->not->toContain('<script')
        ->and($stored)->not->toContain('<h1')
        ->and($stored)->toContain('Pasted Heading')
        ->and($stored)->toContain('Safe');
});
