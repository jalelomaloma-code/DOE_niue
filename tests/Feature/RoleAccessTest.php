<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('lets a super admin reach the panel', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

it('lets a viewer reach the panel', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::Viewer->value);

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

it('refuses a user with no role at all', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('knows which roles may publish', function () {
    expect(UserRole::SuperAdmin->canPublish())->toBeTrue()
        ->and(UserRole::WebsiteManager->canPublish())->toBeTrue()
        ->and(UserRole::Editor->canPublish())->toBeFalse()
        ->and(UserRole::Viewer->canPublish())->toBeFalse();
});
