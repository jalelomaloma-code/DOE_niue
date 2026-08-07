<?php

use App\Models\User;

it('redirects anonymous visitors away from the admin panel', function () {
    $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
});

it('lets an authenticated user reach the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});
