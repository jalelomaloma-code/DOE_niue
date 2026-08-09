<?php

use App\Enums\ContentStatus;
use App\Models\Page;

it('computes a root path from the slug', function () {
    $page = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);

    expect($page->fresh()->path)->toBe('about');
});

it('computes a nested path from its ancestry', function () {
    $parent = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);
    $child = Page::factory()->create(['slug' => 'mandate', 'parent_id' => $parent->id]);

    expect($child->fresh()->path)->toBe('about/mandate');
});

it('recomputes descendant paths when a parent slug changes', function () {
    $parent = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);
    $child = Page::factory()->create(['slug' => 'mandate', 'parent_id' => $parent->id]);

    $parent->update(['slug' => 'about-us']);

    expect($child->fresh()->path)->toBe('about-us/mandate');
});

it('casts status so isPublished works on a freshly loaded record', function () {
    $page = Page::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $fresh = Page::findOrFail($page->id);

    expect($fresh->status)->toBe(ContentStatus::Published)
        ->and($fresh->isPublished())->toBeTrue();
});

it('excludes unpublished and future-dated pages from published()', function () {
    Page::factory()->create(['slug' => 'live', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['slug' => 'draft', 'status' => ContentStatus::Draft, 'published_at' => now()->subDay()]);
    Page::factory()->create(['slug' => 'later', 'status' => ContentStatus::Published, 'published_at' => now()->addWeek()]);

    expect(Page::published()->pluck('slug')->all())->toBe(['live']);
});
