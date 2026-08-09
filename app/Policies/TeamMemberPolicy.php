<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TeamMember;
use App\Models\User;

class TeamMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function view(User $user, TeamMember $teamMember): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function update(User $user, TeamMember $teamMember): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function delete(User $user, TeamMember $teamMember): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
