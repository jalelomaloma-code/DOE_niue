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
