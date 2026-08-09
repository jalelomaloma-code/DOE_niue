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

it('recomputes a grandchild path when the grandparent slug changes', function () {
    // The parent/child test above can't distinguish the cascade condition
    // from the brief's original `wasChanged(['slug', 'parent_id'])`: at one
    // level deep both conditions agree, so that test still passes even if
    // the fix is reverted. A grandchild is the shallowest tree where they
    // diverge — the child's own slug/parent_id never change when the
    // grandparent is renamed, only its `path` does, so only a `path`-based
    // condition keeps propagating far enough to reach the grandchild.
    $grandparent = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);
    $parent = Page::factory()->create(['slug' => 'mandate', 'parent_id' => $grandparent->id]);
    $grandchild = Page::factory()->create(['slug' => 'history', 'parent_id' => $parent->id]);

    expect($grandchild->fresh()->path)->toBe('about/mandate/history');

    $grandparent->update(['slug' => 'about-us']);

    expect($parent->fresh()->path)->toBe('about-us/mandate')
        ->and($grandchild->fresh()->path)->toBe('about-us/mandate/history');
});

it('rejects a page being set as its own parent', function () {
    $page = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);

    expect(fn () => $page->update(['parent_id' => $page->id]))
        ->toThrow(InvalidArgumentException::class, 'A page cannot be its own parent.');
});

it('rejects a transitive cycle through an ancestor', function () {
    $a = Page::factory()->create(['slug' => 'a', 'parent_id' => null]);
    $b = Page::factory()->create(['slug' => 'b', 'parent_id' => $a->id]);
    $c = Page::factory()->create(['slug' => 'c', 'parent_id' => $b->id]);

    // A -> B -> C already. Making C the parent of A would close the loop:
    // A -> C -> B -> A.
    expect(fn () => $a->update(['parent_id' => $c->id]))
        ->toThrow(InvalidArgumentException::class, 'A page cannot be a descendant of itself.');
});
