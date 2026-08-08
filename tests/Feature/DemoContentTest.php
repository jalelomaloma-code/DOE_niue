<?php

use App\Models\Document;
use App\Models\HomepageSetting;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;
use App\Models\QuickLink;
use App\Models\SiteSetting;

it('flags every seeded record as demo content', function () {
    $this->seed(\Database\Seeders\DemoContentSeeder::class);

    expect(Programme::where('is_demo', false)->count())->toBe(0)
        ->and(NewsArticle::where('is_demo', false)->count())->toBe(0)
        ->and(Project::where('is_demo', false)->count())->toBe(0)
        ->and(Document::where('is_demo', false)->count())->toBe(0);
});

it('removes all demo content and leaves real content alone', function () {
    $this->seed(\Database\Seeders\DemoContentSeeder::class);
    $real = Programme::factory()->create(['is_demo' => false, 'title' => 'Real programme']);

    expect(Programme::count())->toBeGreaterThan(1);

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    expect(Programme::count())->toBe(1)
        ->and(Programme::first()->title)->toBe('Real programme')
        ->and(NewsArticle::count())->toBe(0)
        ->and(Document::count())->toBe(0);
});

it('deletes media files from disk, not merely the database rows', function () {
    $this->seed(\Database\Seeders\DemoContentSeeder::class);

    $programme = Programme::whereHas('media')->firstOrFail();
    $media = $programme->getFirstMedia('featured_image');
    $paths = collect($media->getGeneratedConversions()->keys()->push('original'))
        ->map(fn ($conversion) => $conversion === 'original' ? $media->getPath() : $media->getPath($conversion));

    // Every one of the original + its conversions must actually exist on disk
    // before the purge, otherwise this test would prove nothing.
    $paths->each(fn ($path) => expect(file_exists($path))->toBeTrue());

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    $paths->each(fn ($path) => expect(file_exists($path))->toBeFalse());
});

it('never touches site settings, homepage settings or quick links', function () {
    $this->seed(\Database\Seeders\DemoContentSeeder::class);

    SiteSetting::current()->update(['department_name' => 'Niue Department of Environment']);
    HomepageSetting::current()->update(['hero_headline' => 'Protecting Niue']);
    $link = QuickLink::create([
        'label' => 'Survives purge',
        'description' => 'A real quick link, not demo content.',
        'icon' => 'heroicon-o-star',
        'url' => '/survives-purge',
        'sort_order' => 99,
        'is_active' => true,
    ]);

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    expect(SiteSetting::current()->department_name)->toBe('Niue Department of Environment')
        ->and(HomepageSetting::current()->hero_headline)->toBe('Protecting Niue')
        ->and(QuickLink::find($link->id))->not->toBeNull();
});

it('gives every homepage section enough featured content', function () {
    $this->seed(\Database\Seeders\DemoContentSeeder::class);

    expect(Programme::published()->featured()->count())->toBeGreaterThanOrEqual(2)
        ->and(Project::published()->featured()->count())->toBeGreaterThanOrEqual(2)
        ->and(Document::published()->featured()->count())->toBeGreaterThanOrEqual(3)
        ->and(NewsArticle::published()->count())->toBeGreaterThanOrEqual(4);
});
