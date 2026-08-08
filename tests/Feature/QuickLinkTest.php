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

function quickLinkPanelUser(UserRole $role): User
{
    Role::findOrCreate($role->value);

    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

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

it('re-seeding after a URL change updates the existing row instead of leaving a stale duplicate', function () {
    // The seeder is keyed on label, not url, precisely because the six URLs
    // above are placeholders for routes that arrive in Specs 2-4 and will
    // change. Keying on url would make a URL edit + reseed insert a new row
    // and leave the old, still-active row behind on the live homepage. This
    // test pins that behaviour and must fail against url-keying.
    $this->seed(QuickLinkSeeder::class);

    expect(QuickLink::count())->toBe(6);

    // Simulate what a future edit to QuickLinkSeeder::run() would produce:
    // the "Waste & Recycling" row's url has drifted from what the seeder's
    // source array currently says (e.g. the real route landed at a
    // different path than the placeholder). Re-running the *unchanged*
    // seeder should then update that row back to the seeder's url — keyed
    // on label — rather than leaving it alone and inserting a second row
    // for '/waste-and-recycling'.
    $wasteLink = QuickLink::where('label', 'Waste & Recycling')->firstOrFail();
    $wasteLink->update(['url' => '/waste-and-recycling-OLD-PLACEHOLDER']);

    $this->seed(QuickLinkSeeder::class);

    expect(QuickLink::count())->toBe(6)
        ->and(QuickLink::where('label', 'Waste & Recycling')->count())->toBe(1)
        ->and(QuickLink::where('label', 'Waste & Recycling')->value('url'))->toBe('/waste-and-recycling')
        ->and(QuickLink::where('url', '/waste-and-recycling-OLD-PLACEHOLDER')->exists())->toBeFalse();
});

it('lets any panel role reach the quick links resource, consistent with the other content resources', function (UserRole $role) {
    $user = quickLinkPanelUser($role);

    $this->actingAs($user);

    Livewire::test(ListQuickLinks::class)->assertSuccessful();
})->with([
    'super admin' => [UserRole::SuperAdmin],
    'website manager' => [UserRole::WebsiteManager],
    'editor' => [UserRole::Editor],
    'viewer' => [UserRole::Viewer],
]);

it('creates a quick link with a site-relative url through the form', function () {
    $this->actingAs(quickLinkPanelUser(UserRole::SuperAdmin));

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

it('accepts a full https url through the form', function () {
    $this->actingAs(quickLinkPanelUser(UserRole::SuperAdmin));

    Livewire::test(CreateQuickLink::class)
        ->fillForm([
            'label' => 'External Link',
            'url' => 'https://www.example.com/some-page',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(QuickLink::where('url', 'https://www.example.com/some-page')->exists())->toBeTrue();
});

it('rejects a javascript: url as a validation error, not a stored XSS vector', function () {
    $this->actingAs(quickLinkPanelUser(UserRole::SuperAdmin));

    Livewire::test(CreateQuickLink::class)
        ->fillForm([
            'label' => 'Malicious Link',
            'url' => 'javascript:alert(1)',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['url' => 'regex']);

    expect(QuickLink::where('label', 'Malicious Link')->exists())->toBeFalse();
});

it('rejects a data: url', function () {
    $this->actingAs(quickLinkPanelUser(UserRole::SuperAdmin));

    Livewire::test(CreateQuickLink::class)
        ->fillForm([
            'label' => 'Data URL Link',
            'url' => 'data:text/html,<script>alert(1)</script>',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['url' => 'regex']);

    expect(QuickLink::where('label', 'Data URL Link')->exists())->toBeFalse();
});

it('rejects a protocol-relative //host url', function () {
    $this->actingAs(quickLinkPanelUser(UserRole::SuperAdmin));

    Livewire::test(CreateQuickLink::class)
        ->fillForm([
            'label' => 'Protocol Relative Link',
            'url' => '//evil.example.com/phishing',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['url' => 'regex']);

    expect(QuickLink::where('label', 'Protocol Relative Link')->exists())->toBeFalse();
});

it('rejects a duplicate quick link url with a validation error instead of a server error', function () {
    $this->actingAs(quickLinkPanelUser(UserRole::SuperAdmin));

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
    $this->actingAs(quickLinkPanelUser(UserRole::SuperAdmin));

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

it('blocks a viewer from creating or updating a quick link, and lets an editor do both', function () {
    $viewer = quickLinkPanelUser(UserRole::Viewer);
    $editor = quickLinkPanelUser(UserRole::Editor);
    $link = QuickLink::create(['label' => 'Existing', 'url' => '/existing', 'sort_order' => 0]);

    expect($viewer->can('create', QuickLink::class))->toBeFalse()
        ->and($viewer->can('update', $link))->toBeFalse()
        ->and($viewer->can('delete', $link))->toBeFalse()
        ->and($editor->can('create', QuickLink::class))->toBeTrue()
        ->and($editor->can('update', $link))->toBeTrue()
        ->and($editor->can('delete', $link))->toBeFalse();

    $this->actingAs($editor);

    Livewire::test(EditQuickLink::class, ['record' => $link->getKey()])
        ->assertSuccessful();
});

it('actually blocks a viewer at the HTTP layer, not just via the raw Gate check', function () {
    // The ability check above proves the policy's rules; this proves the
    // panel really enforces them at the route/page level (mount ->
    // authorizeCreate()/authorizeEdit(), not just something the app could
    // choose to consult but doesn't).
    $viewer = quickLinkPanelUser(UserRole::Viewer);
    $editor = quickLinkPanelUser(UserRole::Editor);
    $link = QuickLink::create(['label' => 'Existing', 'url' => '/existing', 'sort_order' => 0]);

    $this->actingAs($viewer)
        ->get(CreateQuickLink::getUrl())
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(EditQuickLink::getUrl(['record' => $link->getKey()]))
        ->assertForbidden();

    $this->actingAs($editor)
        ->get(CreateQuickLink::getUrl())
        ->assertSuccessful();

    $this->actingAs($editor)
        ->get(EditQuickLink::getUrl(['record' => $link->getKey()]))
        ->assertSuccessful();
});

it('lets super admin and website manager delete a quick link, but not editor', function () {
    $superAdmin = quickLinkPanelUser(UserRole::SuperAdmin);
    $websiteManager = quickLinkPanelUser(UserRole::WebsiteManager);
    $editor = quickLinkPanelUser(UserRole::Editor);
    $link = QuickLink::create(['label' => 'Existing', 'url' => '/existing', 'sort_order' => 0]);

    expect($superAdmin->can('delete', $link))->toBeTrue()
        ->and($websiteManager->can('delete', $link))->toBeTrue()
        ->and($editor->can('delete', $link))->toBeFalse();
});
