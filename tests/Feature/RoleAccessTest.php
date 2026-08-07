<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('lets a user holding any defined role reach the panel', function (UserRole $role) {
    $user = User::factory()->create();
    $user->assignRole($role->value);

    $this->actingAs($user)->get('/admin')->assertSuccessful();
})->with([
    'super admin' => [UserRole::SuperAdmin],
    'website manager' => [UserRole::WebsiteManager],
    'editor' => [UserRole::Editor],
    'viewer' => [UserRole::Viewer],
]);

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

it('refuses publish to a user holding only editor', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::Editor->value);

    expect($user->mayPublish())->toBeFalse();
});

it('lets a user holding website manager publish', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::WebsiteManager->value);

    expect($user->mayPublish())->toBeTrue();
});

it('lets a user holding both editor and super admin publish', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::Editor->value);
    $user->assignRole(UserRole::SuperAdmin->value);

    expect($user->mayPublish())->toBeTrue();
});
