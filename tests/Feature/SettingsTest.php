<?php

use App\Enums\UserRole;
use App\Filament\Pages\ManageHomepage;
use App\Filament\Pages\ManageSiteSettings;
use App\Models\HomepageSetting;
use App\Models\SiteSetting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

function settingsEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::Editor->value);

    return $user;
}

function settingsWebsiteManager(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::WebsiteManager->value);

    return $user;
}

it('returns a single site settings row, creating it when absent', function () {
    expect(SiteSetting::count())->toBe(0);

    $first = SiteSetting::current();
    $second = SiteSetting::current();

    expect(SiteSetting::count())->toBe(1)
        ->and($first->id)->toBe($second->id)
        ->and($first->department_name)->toBe('Niue Department of Environment');
});

it('returns a single homepage settings row', function () {
    HomepageSetting::current();
    HomepageSetting::current();

    expect(HomepageSetting::count())->toBe(1);
});

it('refuses an editor access to the site settings page', function () {
    $editor = settingsEditor();

    $this->actingAs($editor)
        ->get(ManageSiteSettings::getUrl())
        ->assertForbidden();
});

it('refuses an editor access to the homepage settings page', function () {
    $editor = settingsEditor();

    $this->actingAs($editor)
        ->get(ManageHomepage::getUrl())
        ->assertForbidden();
});

it('lets a website manager access the site settings page', function () {
    $manager = settingsWebsiteManager();

    $this->actingAs($manager)
        ->get(ManageSiteSettings::getUrl())
        ->assertSuccessful();
});

it('lets a website manager access the homepage settings page', function () {
    $manager = settingsWebsiteManager();

    $this->actingAs($manager)
        ->get(ManageHomepage::getUrl())
        ->assertSuccessful();
});

it('saves site settings to the same row rather than creating a second', function () {
    $manager = settingsWebsiteManager();
    $this->actingAs($manager);

    $original = SiteSetting::current();

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['department_name' => 'Updated Department Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(SiteSetting::count())->toBe(1)
        ->and(SiteSetting::current()->id)->toBe($original->id)
        ->and(SiteSetting::current()->department_name)->toBe('Updated Department Name');
});

it('saves homepage settings to the same row rather than creating a second', function () {
    $manager = settingsWebsiteManager();
    $this->actingAs($manager);

    $original = HomepageSetting::current();

    Livewire::test(ManageHomepage::class)
        ->fillForm(['hero_headline' => 'Updated Hero Headline'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(HomepageSetting::count())->toBe(1)
        ->and(HomepageSetting::current()->id)->toBe($original->id)
        ->and(HomepageSetting::current()->hero_headline)->toBe('Updated Hero Headline');
});
