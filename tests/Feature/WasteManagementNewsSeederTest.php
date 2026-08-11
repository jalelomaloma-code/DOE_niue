<?php

use App\Enums\ContentStatus;
use App\Models\NewsArticle;
use Database\Seeders\WasteManagementNewsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('imports all ten waste management archive posts with local images and source attribution', function () {
    Storage::fake('public');

    $this->seed(WasteManagementNewsSeeder::class);

    $articles = NewsArticle::where('author_name', 'Waste Management Niue')->get();
    $schoolArticle = $articles->firstWhere('slug', 'niue-waste-project-supports-primary-and-secondary-schools');

    expect($articles)->toHaveCount(10)
        ->and($articles->every->isPublished())->toBeTrue()
        ->and($articles->contains(fn (NewsArticle $article): bool => $article->is_demo))->toBeFalse()
        ->and($articles->every(fn (NewsArticle $article): bool => $article->hasMedia('featured_image')))->toBeTrue()
        ->and($articles->every(fn (NewsArticle $article): bool => str_contains($article->body, 'originally published')))->toBeTrue()
        ->and($schoolArticle->published_at->toDateString())->toBe('2022-09-30')
        ->and($schoolArticle->getMedia('article_images'))->toHaveCount(1);

    $response = $this->withoutVite()->get(route('news.index'));

    $response->assertOk()
        ->assertSee('Niue Waste Project supports primary and secondary schools')
        ->assertSee('Waste collection pilot supports Lakepa Show Day');
});

it('can be run again without duplicating posts or images', function () {
    Storage::fake('public');

    $this->seed(WasteManagementNewsSeeder::class);
    $this->seed(WasteManagementNewsSeeder::class);

    $articles = NewsArticle::where('author_name', 'Waste Management Niue')->get();

    expect($articles)->toHaveCount(10)
        ->and($articles->every(fn (NewsArticle $article): bool => $article->getMedia('featured_image')->count() === 1))->toBeTrue()
        ->and($articles->sum(fn (NewsArticle $article): int => $article->getMedia('article_images')->count()))->toBe(1);
});

it('ranks imported content ahead of newer placeholder demo articles', function () {
    Storage::fake('public');

    $this->seed(WasteManagementNewsSeeder::class);

    NewsArticle::factory()->create([
        'title' => 'Newer placeholder article',
        'slug' => 'newer-placeholder-article',
        'status' => ContentStatus::Published,
        'published_at' => now(),
        'is_demo' => true,
    ]);

    $this->withoutVite()
        ->get(route('news.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'Niue Waste Project supports primary and secondary schools',
            'Waste collection pilot supports Lakepa Show Day',
            'Newer placeholder article',
        ]);
});
