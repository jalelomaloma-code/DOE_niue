<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

function departmentDashboardUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

it('sends guests to the department panel login before showing the basic dashboard', function () {
    $this->get('/dashboard')->assertRedirect(route('filament.department.auth.login'));
});

it('lets a viewer see the Filament department dashboard with sample data', function () {
    $this->actingAs(departmentDashboardUser(UserRole::Viewer))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Director overview')
        ->assertSee('Decisions, delivery, finances, compliance and environmental response in one place.')
        ->assertSee('Environmental indicators')
        ->assertSee('Website Analytics');
});

it('blocks viewer access to the optional full dashboard preview', function () {
    $this->actingAs(departmentDashboardUser(UserRole::Viewer))
        ->get('/dashboard/full-preview')
        ->assertForbidden();
});

it('lets website managers see the optional full dashboard preview', function () {
    $this->actingAs(departmentDashboardUser(UserRole::WebsiteManager))
        ->get('/dashboard/full-preview')
        ->assertOk()
        ->assertSee('Optional Full Department Dashboard Preview')
        ->assertSee('Director executive summary');
});

it('keeps website management under the separate admin panel', function () {
    $this->actingAs(departmentDashboardUser(UserRole::SuperAdmin))
        ->get('/admin')
        ->assertOk()
        ->assertSee('Website Management');
});
