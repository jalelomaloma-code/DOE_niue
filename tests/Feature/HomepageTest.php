<?php

use App\Enums\ContentStatus;
use App\Models\Document;
use App\Models\HomepageSetting;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;
use App\Models\QuickLink;
use Illuminate\Support\Facades\DB;

it('renders hero content from the database', function () {
    HomepageSetting::current()->update([
        'hero_headline' => "Protecting Niue's Environment for Future Generations",
        'hero_intro' => 'Supporting conservation and biodiversity.',
        'hero_primary_cta_label' => 'Explore Our Work',
    ]);

    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertSee("Protecting Niue's Environment for Future Generations", false);
    $response->assertSee('Explore Our Work');
});

it('shows published programmes and hides unpublished ones', function () {
    Programme::factory()->create([
        'title' => 'Marine Conservation Programme',
        'is_featured' => true,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    Programme::factory()->create([
        'title' => 'Secret Draft Programme',
        'is_featured' => true,
        'status' => ContentStatus::Draft,
    ]);

    $response = $this->withoutVite()->get('/');

    $response->assertSee('Marine Conservation Programme');
    $response->assertDontSee('Secret Draft Programme');
});

it('shows at most four news articles, newest first', function () {
    foreach (range(1, 6) as $i) {
        NewsArticle::factory()->create([
            'title' => "Article {$i}",
            'status' => ContentStatus::Published,
            'published_at' => now()->subDays(10 - $i),
        ]);
    }

    $response = $this->withoutVite()->get('/');

    $response->assertSee('Article 6');
    $response->assertDontSee('Article 1');
});

it('hides a section entirely when it has no content', function () {
    HomepageSetting::current()->update(['news_heading' => 'Latest News']);

    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertDontSee('Latest News');
});

it('renders without error on a completely empty database', function () {
    $this->withoutVite()->get('/')->assertOk();
});

it('renders active quick links', function () {
    QuickLink::create(['label' => 'Waste & Recycling', 'url' => '/waste-and-recycling', 'sort_order' => 1]);
    QuickLink::create(['label' => 'Hidden Link', 'url' => '/hidden', 'sort_order' => 2, 'is_active' => false]);

    $response = $this->withoutVite()->get('/');

    $response->assertSee('Waste &amp; Recycling', false);
    $response->assertDontSee('Hidden Link');
});

it('does not run a query per card to resolve featured images', function () {
    Programme::factory()->count(4)->create([
        'is_featured' => true,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    NewsArticle::factory()->count(4)->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    Project::factory()->count(3)->create([
        'is_featured' => true,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    Document::factory()->count(5)->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
        'published_date' => now()->subDay(),
    ]);

    DB::enableQueryLog();
    $this->withoutVite()->get('/')->assertOk();
    $queryCount = count(DB::getQueryLog());
    DB::flushQueryLog();
    DB::disableQueryLog();

    // Without eager-loaded media, 4 programmes + 4 news + 3 projects +
    // 5 documents each resolve their image via a separate lazy-loaded
    // `media` query — at least 16 extra queries. A generous ceiling well
    // below that catches a regression without being brittle to unrelated
    // query-count drift. (Measured directly: 28 queries before the
    // ->with('media') fix, 16 after.)
    expect($queryCount)->toBeLessThan(25);
});

it('respects a curated sort_order in the fallback, not just the featured path', function () {
    // None of these are featured, so featuredOrLatest() falls through to its
    // fallback query. sort_order is a deliberate editorial choice by the
    // Department; the fallback must honour it rather than silently
    // reverting to "most recent", which would discard that curation the
    // moment nobody ticks "featured".
    Programme::factory()->create([
        'title' => 'Third In Order',
        'sort_order' => 3,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDays(1),
    ]);
    Programme::factory()->create([
        'title' => 'First In Order',
        'sort_order' => 1,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDays(10),
    ]);
    Programme::factory()->create([
        'title' => 'Second In Order',
        'sort_order' => 2,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDays(5),
    ]);

    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertSeeInOrder(['First In Order', 'Second In Order', 'Third In Order']);
});
