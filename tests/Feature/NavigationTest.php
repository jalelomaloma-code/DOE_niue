<?php

use App\Enums\UserRole;
use App\Filament\Resources\NavigationItems\Pages\CreateNavigationItem;
use App\Models\NavigationItem;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function navigationPanelUser(UserRole $role): User
{
    Role::findOrCreate($role->value);

    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('returns active items in sort order', function () {
    NavigationItem::create(['label' => 'Second', 'url' => '/b', 'sort_order' => 2]);
    NavigationItem::create(['label' => 'First', 'url' => '/a', 'sort_order' => 1]);
    NavigationItem::create(['label' => 'Hidden', 'url' => '/c', 'sort_order' => 0, 'is_active' => false]);

    expect(NavigationItem::active()->pluck('label')->all())->toBe(['First', 'Second']);
});

it('renders the header from the database', function () {
    NavigationItem::create(['label' => 'Waste & Recycling', 'url' => '/waste-and-recycling', 'sort_order' => 1]);

    $this->withoutVite()->get('/')->assertSee('Waste &amp; Recycling', false);
});

it('omits an inactive item from the header', function () {
    NavigationItem::create(['label' => 'Retired Section', 'url' => '/retired', 'sort_order' => 1, 'is_active' => false]);

    $this->withoutVite()->get('/')->assertDontSee('Retired Section');
});

it('seeds the six navigation items', function () {
    // The brief's Step 2 draft predates the 2026-08-08 regroup recorded in
    // NavigationItemSeeder (twelve flat items -> six top-level sections);
    // this assertion tracks the seeder that actually ships, not the stale
    // draft count.
    $this->seed(\Database\Seeders\NavigationItemSeeder::class);

    expect(NavigationItem::active()->count())->toBe(6);
});

it('re-seeding after a URL change updates the existing row instead of leaving a stale duplicate', function () {
    // The seeder is keyed on label, not url, precisely because the six URLs
    // in NavigationItemSeeder are placeholders for routes later specs will
    // change. Keying on url would make a URL edit + reseed insert a new row
    // and leave the old, still-active row behind live in the header. This
    // test pins that behaviour and must fail against url-keying.
    $this->seed(\Database\Seeders\NavigationItemSeeder::class);

    expect(NavigationItem::count())->toBe(6);

    // Simulate what a future edit to NavigationItemSeeder::run() would
    // produce: the "Resources" row's url has drifted from what the seeder's
    // source array currently says (e.g. the real route landed at a
    // different path than the placeholder). Re-running the *unchanged*
    // seeder should then update that row back to the seeder's url — keyed
    // on label — rather than leaving it alone and inserting a second row
    // for '/resources'.
    $resourcesItem = NavigationItem::where('label', 'Resources')->firstOrFail();
    $resourcesItem->update(['url' => '/resources-OLD-PLACEHOLDER']);

    $this->seed(\Database\Seeders\NavigationItemSeeder::class);

    expect(NavigationItem::count())->toBe(6)
        ->and(NavigationItem::where('label', 'Resources')->count())->toBe(1)
        ->and(NavigationItem::where('label', 'Resources')->value('url'))->toBe('/resources')
        ->and(NavigationItem::where('url', '/resources-OLD-PLACEHOLDER')->exists())->toBeFalse();
});

it('accepts a site-relative url through the form', function () {
    $this->actingAs(navigationPanelUser(UserRole::SuperAdmin));

    Livewire::test(CreateNavigationItem::class)
        ->fillForm([
            'label' => 'Test Item',
            'url' => '/some-relative-path',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(NavigationItem::where('url', '/some-relative-path')->exists())->toBeTrue();
});

it('rejects a javascript: url as a validation error, not a stored XSS vector', function () {
    // Same regex, same reasoning as QuickLinkForm: Filament's own ->url()
    // rule requires a scheme and would reject every site-relative path this
    // form needs to accept, so it's replaced with a narrower pattern —
    // which must still reject a javascript: scheme, since Blade's {{ }}
    // does not neutralise one inside an href attribute.
    $this->actingAs(navigationPanelUser(UserRole::SuperAdmin));

    Livewire::test(CreateNavigationItem::class)
        ->fillForm([
            'label' => 'Malicious Item',
            'url' => 'javascript:alert(1)',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['url' => 'regex']);

    expect(NavigationItem::where('label', 'Malicious Item')->exists())->toBeFalse();
});
