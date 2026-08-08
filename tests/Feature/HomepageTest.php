<?php

use App\Enums\ContentStatus;
use App\Models\HomepageSetting;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\QuickLink;

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
