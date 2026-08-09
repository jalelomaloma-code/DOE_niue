<?php

use App\Enums\ContentStatus;
use App\Models\Page;

it('serves a published root page', function () {
    Page::factory()->create(['title' => 'About Us', 'slug' => 'about', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);

    $this->withoutVite()->get('/about')->assertOk()->assertSee('About Us');
});

it('serves a published child page at its nested path', function () {
    $parent = Page::factory()->create(['slug' => 'about', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Mandate', 'slug' => 'mandate', 'parent_id' => $parent->id, 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);

    $this->withoutVite()->get('/about/mandate')->assertOk()->assertSee('Mandate');
});

it('404s an unpublished page', function () {
    Page::factory()->create(['slug' => 'secret', 'status' => ContentStatus::Draft]);

    $this->withoutVite()->get('/secret')->assertNotFound();
});

it('404s a future-dated page', function () {
    Page::factory()->create(['slug' => 'soon', 'status' => ContentStatus::Published, 'published_at' => now()->addWeek()]);

    $this->withoutVite()->get('/soon')->assertNotFound();
});

it('does not shadow the admin panel', function () {
    $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
});

it('lists published children on a section landing page', function () {
    $parent = Page::factory()->create(['title' => 'About Us', 'slug' => 'about', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Mandate', 'slug' => 'mandate', 'parent_id' => $parent->id, 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Hidden Child', 'slug' => 'hidden', 'parent_id' => $parent->id, 'status' => ContentStatus::Draft]);

    $response = $this->withoutVite()->get('/about');

    $response->assertSee('Mandate');
    $response->assertDontSee('Hidden Child');
});

it('resolves a two-segment path under a section other than about, proving the pattern is not hardcoded to one parent slug', function () {
    // The information architecture regroups topic sections under a new
    // "Our Work" parent (spec 3a), so /about/mandate alone can't prove the
    // route pattern generalises — it could coincidentally only match
    // "about/*". This uses a differently-named parent/child pair to prove
    // the two-segment regex group is genuinely generic.
    $parent = Page::factory()->create(['title' => 'Our Work', 'slug' => 'our-work', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Waste and Recycling', 'slug' => 'waste-and-recycling', 'parent_id' => $parent->id, 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);

    $this->withoutVite()->get('/our-work/waste-and-recycling')->assertOk()->assertSee('Waste and Recycling');
});

it('shows sibling navigation with aria-current on the active child', function () {
    $parent = Page::factory()->create(['slug' => 'our-work', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Waste and Recycling', 'slug' => 'waste-and-recycling', 'parent_id' => $parent->id, 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Climate and Marine', 'slug' => 'climate-and-marine', 'parent_id' => $parent->id, 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);

    $response = $this->withoutVite()->get('/our-work/waste-and-recycling');

    $response->assertOk();
    $response->assertSee('aria-current="page"', false);
    $response->assertSee('Climate and Marine');
});
