<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('redirects anonymous visitors away from the admin panel', function () {
    $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
});

it('lets an authenticated user with a role reach the admin panel', function () {
    Role::findOrCreate(UserRole::Viewer->value);

    $user = User::factory()->create();
    $user->assignRole(UserRole::Viewer->value);

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});
