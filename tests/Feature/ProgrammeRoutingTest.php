<?php

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Models\Programme;
use App\Models\Project;

it('serves a published programme', function () {
    $programme = Programme::factory()->create([
        'title' => 'Marine Conservation Programme',
        'slug' => 'marine-conservation',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $html = $this->withoutVite()->get("/our-work/environment-programmes/{$programme->slug}")
        ->assertOk()
        ->getContent();

    // assertSee() alone would pass even if the <h1> were broken, because
    // the same title also renders unconditionally in the <title> tag via
    // seoTitle(). Pin the <h1> itself, and that there is exactly one.
    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)->toMatch('/<h1[^>]*>\s*Marine Conservation Programme\s*<\/h1>/');
});

it('404s an unpublished programme', function () {
    $programme = Programme::factory()->create(['slug' => 'draft-programme', 'status' => ContentStatus::Draft]);

    $this->withoutVite()->get("/our-work/environment-programmes/{$programme->slug}")->assertNotFound();
});

it('links programme cards to their detail page', function () {
    $programme = Programme::factory()->create([
        'title' => 'Waste Programme',
        'slug' => 'waste-programme',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
        'is_featured' => true,
    ]);

    $this->withoutVite()->get('/')->assertSee("/our-work/environment-programmes/{$programme->slug}", false);
});

it('shows only published related projects', function () {
    $programme = Programme::factory()->create([
        'slug' => 'reef-restoration',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    Project::factory()->create([
        'programme_id' => $programme->id,
        'title' => 'Live Reef Survey',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    Project::factory()->create([
        'programme_id' => $programme->id,
        'title' => 'Draft Reef Survey',
        'status' => ContentStatus::Draft,
    ]);

    $response = $this->withoutVite()->get("/our-work/environment-programmes/{$programme->slug}");

    $response->assertOk();
    $response->assertSee('Live Reef Survey');
    $response->assertDontSee('Draft Reef Survey');
});

it('hides the related projects section when there are none', function () {
    $programme = Programme::factory()->create([
        'slug' => 'no-projects-here',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->withoutVite()->get("/our-work/environment-programmes/{$programme->slug}");

    $response->assertOk();
    $response->assertDontSee('Related Projects');
});

it('resolves the three-segment programme detail route ahead of the page catch-all', function () {
    // The catch-all's regex only permits one or two path segments, so a
    // three-segment programme URL could never match it even if ordering
    // were wrong — but a landing Page at the same two-segment prefix must
    // not swallow the third segment, and the explicit route must win.
    $section = Page::factory()->create([
        'title' => 'Our Work', 'slug' => 'our-work',
        'status' => ContentStatus::Published, 'published_at' => now()->subDay(),
    ]);
    Page::factory()->create([
        'title' => 'Environment Programmes', 'slug' => 'environment-programmes', 'parent_id' => $section->id,
        'status' => ContentStatus::Published, 'published_at' => now()->subDay(),
    ]);

    $programme = Programme::factory()->create([
        'title' => 'Coral Watch Programme',
        'slug' => 'coral-watch',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $this->withoutVite()->get('/our-work/environment-programmes/coral-watch')
        ->assertOk()
        ->assertSee('Coral Watch Programme');
});

/*
 * Spec 2 success criterion 4: heading hierarchy is correct on every page
 * regardless of what editors typed. The first test in this file also counts
 * <h1>s, but its factory body contains no heading at all, so it would pass
 * even with sanitisation removed entirely. This one deliberately pastes an
 * <h1> into the body and drives the real render path, so the count is only
 * 1 if the model mutator actually unwrapped it.
 */
it('renders exactly one h1 even when the programme body contains a pasted h1', function () {
    $programme = Programme::factory()->create([
        'title' => 'Lagoon Water Quality Programme',
        'slug' => 'lagoon-water-quality',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
        'body' => '<h1>Background</h1><p>Monitoring the lagoon.</p>',
    ]);

    $html = $this->withoutVite()->get("/our-work/environment-programmes/{$programme->slug}")
        ->assertOk()
        ->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
    // The words survive as a paragraph -- blockElement(), not dropElement().
    expect($html)->toContain('Background');
});
