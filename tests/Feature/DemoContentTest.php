<?php

use App\Models\Document;
use App\Models\HomepageSetting;
use App\Models\NavigationItem;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Programme;
use App\Models\Project;
use App\Models\QuickLink;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\NavigationItemSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\TeamMemberSeeder;

it('flags every seeded record as demo content', function () {
    $this->seed(DemoContentSeeder::class);

    expect(Programme::where('is_demo', false)->count())->toBe(0)
        ->and(NewsArticle::where('is_demo', false)->count())->toBe(0)
        ->and(Project::where('is_demo', false)->count())->toBe(0)
        ->and(Document::where('is_demo', false)->count())->toBe(0);
});

it('removes all demo content and leaves real content alone', function () {
    $this->seed(DemoContentSeeder::class);
    $real = Programme::factory()->create(['is_demo' => false, 'title' => 'Real programme']);

    expect(Programme::count())->toBeGreaterThan(1);

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    expect(Programme::count())->toBe(1)
        ->and(Programme::first()->title)->toBe('Real programme')
        ->and(NewsArticle::count())->toBe(0)
        ->and(Document::count())->toBe(0);
});

// Deliberately does NOT Storage::fake('public'), unlike the other Resource
// tests. A fake disk would only prove medialibrary's own delete hook runs
// against a temporary directory Laravel throws away anyway -- it can't prove
// files are gone from the disk the app actually serves. Asserting against
// real paths is what lets this test (and it alone) prove demo:purge cleans
// up storage, not just rows. The cost: repeated `pest` runs leave small,
// harmless, gitignored files behind under storage/app/public/{id}/, since
// RefreshDatabase truncates tables but never touches physical storage.
it('deletes media files from disk, not merely the database rows', function () {
    $this->seed(DemoContentSeeder::class);

    $pathsFor = fn ($media) => collect($media->getGeneratedConversions()->keys()->push('original'))
        ->map(fn ($conversion) => $conversion === 'original' ? $media->getPath() : $media->getPath($conversion));

    // Programme::featured_image and Document::file are two different models
    // and two different media collections -- covering both means a
    // regression in either the model's registered collection or the
    // purge command's model list would actually be caught here.
    $programme = Programme::whereHas('media')->firstOrFail();
    $document = Document::whereHas('media')->firstOrFail();

    $paths = $pathsFor($programme->getFirstMedia('featured_image'))
        ->merge($pathsFor($document->getFirstMedia('file')));

    // Every one of the original + its conversions must actually exist on disk
    // before the purge, otherwise this test would prove nothing.
    $paths->each(fn ($path) => expect(file_exists($path))->toBeTrue());

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    $paths->each(fn ($path) => expect(file_exists($path))->toBeFalse());
});

it('never touches site settings, homepage settings or quick links', function () {
    $this->seed(DemoContentSeeder::class);

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
    $this->seed(DemoContentSeeder::class);

    expect(Programme::published()->featured()->count())->toBeGreaterThanOrEqual(2)
        ->and(Project::published()->featured()->count())->toBeGreaterThanOrEqual(2)
        ->and(Document::published()->featured()->count())->toBeGreaterThanOrEqual(3)
        ->and(NewsArticle::published()->count())->toBeGreaterThanOrEqual(4);
});

it('purges demo pages and team members but keeps navigation items', function () {
    $this->seed(NavigationItemSeeder::class);
    $this->seed(PageSeeder::class);
    $this->seed(TeamMemberSeeder::class);

    expect(Page::count())->toBeGreaterThan(0)
        ->and(TeamMember::count())->toBeGreaterThan(0);

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    expect(Page::count())->toBe(0)
        ->and(TeamMember::count())->toBe(0)
        // NavigationItemSeeder seeds six top-level items post-regroup (Home,
        // About Us, Our Work, News & Events, Resources, Contact Us) -- not
        // twelve. NavigationItem has no is_demo column at all, so this count
        // must be exactly what was seeded, unchanged by the purge.
        ->and(NavigationItem::count())->toBe(6);
});

it('resolves every seeded navigation path and every child of About Us and Our Work', function () {
    // This is the spec's headline success criterion ("every navigation item
    // resolves; no 404 from the primary navigation") made into a machine
    // check instead of resting on a manual browser walkthrough. The
    // pre-existing route tests (PageRoutingTest) only ever exercise
    // Page::factory() fixtures, so they would keep passing even if
    // PageSeeder produced completely different slugs from what the
    // navigation actually points at -- this test is the one that would
    // catch that drift.
    $this->seed(NavigationItemSeeder::class);
    $this->seed(PageSeeder::class);

    // Derived from the seeded records, not hardcoded, so this test tracks
    // NavigationItemSeeder/PageSeeder rather than drifting from them.
    $navUrls = NavigationItem::active()->pluck('url');

    $about = Page::published()->where('slug', 'about')->firstOrFail();
    $ourWork = Page::published()->where('slug', 'our-work')->firstOrFail();

    $childPaths = $about->children()->published()->pluck('path')
        ->merge($ourWork->children()->published()->pluck('path'))
        ->map(fn (string $path) => "/{$path}");

    // Sanity check on the derivation itself: if PageSeeder regressed to
    // creating zero children, the loop below would still pass vacuously.
    expect($childPaths)->toHaveCount(8);

    $expectedToResolve = $navUrls
        ->merge($childPaths)
        ->unique()
        ->values();

    // Six top-level navigation destinations plus the eight section children.
    expect($expectedToResolve)->toHaveCount(6 + 8);

    foreach ($expectedToResolve as $url) {
        $this->withoutVite()->get($url)->assertOk();
    }

});
