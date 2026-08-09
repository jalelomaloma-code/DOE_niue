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

    // Pinned to an exact budget, not a loose ceiling — toBeLessThan(25)
    // let a real regression through silently: dropping with('media') from
    // BOTH featuredOrLatest() builders (programmes and projects) while
    // news and documents keep theirs still only reaches 21 (2 eager
    // queries traded for 7 lazy per-card ones: 4 programmes + 3 projects
    // seeded above), comfortably under 25.
    //
    // The 19 queries this request actually makes, in order, on a fresh
    // test database (RefreshDatabase, no settings seeded yet):
    //   1-2   homepage_settings: select (none found) + firstOrCreate insert
    //   3     quick_links: active() select
    //   4-5   programmes: featured() list + with('media') eager load
    //   6-7   news_articles: published() list + with('media') eager load
    //   8-9   projects: featured() list + with('media') eager load
    //   10-11 documents: published() list + with('media') eager load
    //   12    homepage_settings: lazy hero-image media lookup
    //   13-15 site_settings (header's SiteSetting::current()): select
    //         (none found) + firstOrCreate insert + fresh() re-select
    //         (current() must re-fetch after insertGetId(), see
    //         SiteSetting::current())
    //   16    navigation_items (header's NavigationItem::active(), Task 10):
    //         select — the View::composer resolves this once and shares it
    //         with both the header's desktop <ul> and its mobile <details>
    //         list, so the header itself never queries it twice
    //   17    site_settings (footer's independent View::composer call):
    //         select — the row exists now, so just one query
    //   18    navigation_items (footer's independent View::composer call):
    //         select — the composer fires once per matching component, so
    //         header and footer each cause one navigation_items query; the
    //         "resolve once" guarantee is about the header's own two lists,
    //         not about header vs. footer sharing a single request-wide
    //         query
    //   19    pages: Page::published() lookup for the footer's Privacy/
    //         Terms/Accessibility column (Task 10)
    //
    // Content-section eager loading (4-11) is what this test exists to
    // protect; the settings/quick-link/navigation queries (1-3, 12-19) are
    // fixed overhead unrelated to card rendering. If a future spec
    // legitimately changes that overhead (e.g. caching settings, adding a
    // homepage section), update this number deliberately rather than
    // loosening it back into a ceiling.
    expect($queryCount)->toBe(19);
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
