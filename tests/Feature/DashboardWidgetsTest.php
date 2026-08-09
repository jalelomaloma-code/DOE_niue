<?php

use App\Enums\ContentStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Filament\Widgets\ContentOverview;
use App\Filament\Widgets\ProjectLifecycle;
use App\Filament\Widgets\RecentNews;
use App\Filament\Widgets\RecentPublications;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\Programme;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

function dashboardSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);

    return $user;
}

/**
 * Reflects into a Stat-returning widget's protected getStats() so tests can
 * assert on the actual Stat objects (label/value/description) rather than
 * scraping rendered HTML for numbers that could collide with something else
 * on the page.
 *
 * @return array<\Filament\Widgets\StatsOverviewWidget\Stat>
 */
function widgetStats(\Filament\Widgets\StatsOverviewWidget $widget): array
{
    $method = new ReflectionMethod($widget, 'getStats');
    $method->setAccessible(true);

    return $method->invoke($widget);
}

// --- Stock widget removal -------------------------------------------------

it('removes the stock Filament account and info widgets from the admin panel', function () {
    $widgets = Filament::getPanel('admin')->getWidgets();

    expect($widgets)->not->toContain(AccountWidget::class)
        ->and($widgets)->not->toContain(FilamentInfoWidget::class);
});

it('registers the four dashboard widgets on the admin panel', function () {
    $widgets = Filament::getPanel('admin')->getWidgets();

    expect($widgets)->toContain(ContentOverview::class)
        ->and($widgets)->toContain(ProjectLifecycle::class)
        ->and($widgets)->toContain(RecentNews::class)
        ->and($widgets)->toContain(RecentPublications::class);
});

// --- Renders for a Super Admin, including on an empty database -----------

it('renders the admin dashboard for a super admin', function () {
    $this->actingAs(dashboardSuperAdmin())
        ->get('/admin')
        ->assertSuccessful();
});

it('renders every widget for a super admin on a completely empty database', function () {
    $this->actingAs(dashboardSuperAdmin());

    Livewire::test(ContentOverview::class)->assertSuccessful();
    Livewire::test(ProjectLifecycle::class)->assertSuccessful();
    Livewire::test(RecentNews::class)->assertSuccessful();
    Livewire::test(RecentPublications::class)->assertSuccessful();
});

it('shows a friendly empty state rather than a zero breakdown when a content type has no records', function () {
    $this->actingAs(dashboardSuperAdmin());

    $stats = widgetStats(new ContentOverview);

    foreach ($stats as $stat) {
        expect($stat->getValue())->toBe('0')
            ->and($stat->getDescription())->toBe('None yet');
    }
});

// --- Content Overview: correct counts, broken down by status -------------

it('breaks content overview counts down by status without hiding drafts or under-review items', function () {
    Programme::factory()->count(2)->create(['status' => ContentStatus::Draft]);
    Programme::factory()->count(1)->create(['status' => ContentStatus::UnderReview]);
    Programme::factory()->count(3)->create(['status' => ContentStatus::Published]);
    Programme::factory()->count(1)->create(['status' => ContentStatus::Archived]);

    $stats = widgetStats(new ContentOverview);
    $programmes = collect($stats)->first(fn ($stat) => $stat->getLabel() === 'Programmes');

    // Total is 7. If the widget mistakenly used the published() scope, this
    // would read 3 (published() also excludes anything without a past
    // published_at), silently hiding the drafts and the under-review item
    // this dashboard exists to surface.
    expect($programmes->getValue())->toBe('7')
        ->and($programmes->getDescription())->toBe('2 Draft · 1 Under Review · 3 Published · 1 Archived');
});

it('covers all four content models in the content overview widget', function () {
    Project::factory()->create(['status' => ContentStatus::Published]);
    NewsArticle::factory()->count(2)->create(['status' => ContentStatus::Draft]);
    Document::factory()->count(4)->create(['status' => ContentStatus::Published]);

    $stats = collect(widgetStats(new ContentOverview))->keyBy(fn ($stat) => $stat->getLabel());

    expect($stats['Projects']->getValue())->toBe('1')
        ->and($stats['News Articles']->getValue())->toBe('2')
        ->and($stats['News Articles']->getDescription())->toBe('2 Draft · 0 Under Review · 0 Published · 0 Archived')
        ->and($stats['Documents']->getValue())->toBe('4');
});

// --- Project Lifecycle: correct counts by project_status -----------------

it('counts projects by lifecycle status', function () {
    Project::factory()->count(2)->create(['project_status' => ProjectStatus::Planned]);
    Project::factory()->count(3)->create(['project_status' => ProjectStatus::Active]);
    Project::factory()->count(1)->create(['project_status' => ProjectStatus::Completed]);
    Project::factory()->count(4)->create(['project_status' => ProjectStatus::OnHold]);

    $stats = collect(widgetStats(new ProjectLifecycle))->keyBy(fn ($stat) => $stat->getLabel());

    expect($stats['Planned']->getValue())->toBe('2')
        ->and($stats['Active']->getValue())->toBe('3')
        ->and($stats['Completed']->getValue())->toBe('1')
        ->and($stats['On Hold']->getValue())->toBe('4');
});

it('does not use the published() scope for project lifecycle counts', function () {
    Project::factory()->create([
        'project_status' => ProjectStatus::Planned,
        'status' => ContentStatus::Draft,
    ]);

    $stats = collect(widgetStats(new ProjectLifecycle))->keyBy(fn ($stat) => $stat->getLabel());

    expect($stats['Planned']->getValue())->toBe('1');
});

// --- Recent News table widget ---------------------------------------------

it('lists the most recent news articles including drafts, with title, category, status and publish date', function () {
    $category = NewsCategory::factory()->create(['name' => 'Announcements']);

    NewsArticle::factory()->create([
        'title' => 'Older published article',
        'news_category_id' => $category->id,
        'status' => ContentStatus::Published,
        'created_at' => now()->subDays(5),
    ]);
    NewsArticle::factory()->create([
        'title' => 'Newest draft article',
        'news_category_id' => null,
        'status' => ContentStatus::Draft,
        'created_at' => now(),
    ]);

    $this->actingAs(dashboardSuperAdmin());

    Livewire::test(RecentNews::class)
        ->assertSuccessful()
        ->assertSee('Newest draft article')
        ->assertSee('Older published article')
        ->assertSee('Announcements');
});

it('does not fatal when a news article category has been deleted', function () {
    $category = NewsCategory::factory()->create();
    $article = NewsArticle::factory()->create(['news_category_id' => $category->id]);
    $category->delete();

    expect($article->fresh()->news_category_id)->toBeNull();

    $this->actingAs(dashboardSuperAdmin());

    Livewire::test(RecentNews::class)->assertSuccessful();
});

// --- Recent Publications table widget --------------------------------------

it('lists the most recent documents including drafts, with title, category, file type and publish date', function () {
    $category = DocumentCategory::factory()->create(['name' => 'Policies']);

    Document::factory()->create([
        'title' => 'Older published document',
        'document_category_id' => $category->id,
        'status' => ContentStatus::Published,
        'created_at' => now()->subDays(5),
    ]);
    Document::factory()->create([
        'title' => 'Newest draft document',
        'document_category_id' => null,
        'status' => ContentStatus::Draft,
        'created_at' => now(),
    ]);

    $this->actingAs(dashboardSuperAdmin());

    Livewire::test(RecentPublications::class)
        ->assertSuccessful()
        ->assertSee('Newest draft document')
        ->assertSee('Older published document')
        ->assertSee('Policies');
});

it('does not fatal when a document category has been deleted', function () {
    $category = DocumentCategory::factory()->create();
    $document = Document::factory()->create(['document_category_id' => $category->id]);
    $category->delete();

    expect($document->fresh()->document_category_id)->toBeNull();

    $this->actingAs(dashboardSuperAdmin());

    Livewire::test(RecentPublications::class)->assertSuccessful();
});

// --- Query budget -----------------------------------------------------------

it('loads the whole dashboard in a bounded number of queries', function () {
    // A handful of records per model, matching the shape of the seeded
    // demo content, so the query count reflects a realistic load rather
    // than an empty-table best case.
    Programme::factory()->count(6)->create();
    NewsArticle::factory()->count(5)->create(['news_category_id' => NewsCategory::factory()]);
    Project::factory()->count(4)->create();
    Document::factory()->count(6)->create(['document_category_id' => DocumentCategory::factory()]);

    $user = dashboardSuperAdmin();

    // Warm anything that only happens once per process (e.g. permission
    // caching) before measuring, so the count reflects steady-state.
    $this->actingAs($user)->get('/admin');

    DB::enableQueryLog();
    Livewire::test(ContentOverview::class);
    Livewire::test(ProjectLifecycle::class);
    Livewire::test(RecentNews::class);
    Livewire::test(RecentPublications::class);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Pinned to an exact, itemised budget rather than a loose ceiling --
    // see task-18-report.md for how this was measured. Ten queries total:
    //   ContentOverview   4  (one grouped status aggregate per model:
    //                         programmes, projects, news_articles, documents)
    //   ProjectLifecycle  1  (one grouped project_status aggregate)
    //   RecentNews        2  (articles + eager-loaded categories)
    //   RecentPublications 3 (documents + eager-loaded categories + media,
    //                         the latter needed by fileType())
    // If this grows, check first for a dropped ->with() before raising it.
    expect($queryCount)->toBe(10);
});
