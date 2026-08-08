<?php

use App\Enums\UserRole;
use App\Filament\Resources\QuickLinks\Pages\CreateQuickLink;
use App\Filament\Resources\QuickLinks\Pages\EditQuickLink;
use App\Filament\Resources\QuickLinks\Pages\ListQuickLinks;
use App\Models\QuickLink;
use App\Models\User;
use Database\Seeders\QuickLinkSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('returns active links in sort order', function () {
    QuickLink::create(['label' => 'Second', 'url' => '/b', 'sort_order' => 2]);
    QuickLink::create(['label' => 'First', 'url' => '/a', 'sort_order' => 1]);
    QuickLink::create(['label' => 'Hidden', 'url' => '/c', 'sort_order' => 0, 'is_active' => false]);

    expect(QuickLink::active()->pluck('label')->all())->toBe(['First', 'Second']);
});

it('seeds the six quick links from the brief', function () {
    $this->seed(QuickLinkSeeder::class);

    expect(QuickLink::active()->count())->toBe(6);
});

it('excludes an inactive link from the active scope', function () {
    $link = QuickLink::create(['label' => 'Solo', 'url' => '/solo', 'sort_order' => 0]);

    expect(QuickLink::active()->pluck('label')->all())->toBe(['Solo']);

    $link->update(['is_active' => false]);

    expect(QuickLink::active()->count())->toBe(0);
});

it('reflects a reorder in what the active scope returns first', function () {
    // Created in the opposite of their intended sort_order, so a passing
    // assertion can only come from an explicit ORDER BY, not row/insertion
    // order (which Postgres is not obliged to preserve, but frequently does
    // for untouched rows — a trap that makes a same-order fixture a weak
    // test here).
    $a = QuickLink::create(['label' => 'A', 'url' => '/a', 'sort_order' => 1]);
    $b = QuickLink::create(['label' => 'B', 'url' => '/b', 'sort_order' => 0]);

    expect(QuickLink::active()->pluck('label')->all())->toBe(['B', 'A']);

    // Now actually reorder them via update and confirm the scope picks it up.
    $a->update(['sort_order' => 0]);
    $b->update(['sort_order' => 1]);

    expect(QuickLink::active()->pluck('label')->all())->toBe(['A', 'B']);
});

it('lets any panel role reach the quick links resource, consistent with the other content resources', function (UserRole $role) {
    Role::findOrCreate($role->value);

    $user = User::factory()->create();
    $user->assignRole($role->value);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ListQuickLinks::class)->assertSuccessful();
})->with([
    'super admin' => [UserRole::SuperAdmin],
    'website manager' => [UserRole::WebsiteManager],
    'editor' => [UserRole::Editor],
    'viewer' => [UserRole::Viewer],
]);

it('creates a quick link with a site-relative url through the form', function () {
    Role::findOrCreate(UserRole::SuperAdmin->value);
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(CreateQuickLink::class)
        ->fillForm([
            'label' => 'Test Link',
            'description' => 'A test link',
            'icon' => 'heroicon-o-globe-alt',
            'url' => '/some-relative-path',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(QuickLink::where('url', '/some-relative-path')->exists())->toBeTrue();
});

it('rejects a duplicate quick link url with a validation error instead of a server error', function () {
    Role::findOrCreate(UserRole::SuperAdmin->value);
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    QuickLink::create(['label' => 'Existing', 'url' => '/dup', 'sort_order' => 0]);

    Livewire::test(CreateQuickLink::class)
        ->fillForm([
            'label' => 'New',
            'url' => '/dup',
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['url' => 'unique']);

    expect(QuickLink::where('url', '/dup')->count())->toBe(1);
});

it('lets a quick link be edited and saved unchanged without tripping its own uniqueness rule', function () {
    Role::findOrCreate(UserRole::SuperAdmin->value);
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $link = QuickLink::create(['label' => 'Existing', 'url' => '/same', 'sort_order' => 0]);

    Livewire::test(EditQuickLink::class, ['record' => $link->getKey()])
        ->fillForm([
            'label' => 'Existing',
            'url' => '/same',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(QuickLink::where('url', '/same')->count())->toBe(1);
});
