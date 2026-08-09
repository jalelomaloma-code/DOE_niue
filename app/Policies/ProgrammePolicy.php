<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Programme;
use App\Models\User;

class ProgrammePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function view(User $user, Programme $programme): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function update(User $user, Programme $programme): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    // No publish ability, deliberately -- see the comment on the status
    // Select's ->options() closure in ProgrammeForm, which is where publishing is
    // actually enforced. A publish() here was called by nothing but tests,
    // and adding a second, policy-based path would duplicate an enforcement
    // that already runs server-side, with a different error surface.
    // TeamMemberPolicy and NavigationItemPolicy have never carried one either.

    public function delete(User $user, Programme $programme): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
