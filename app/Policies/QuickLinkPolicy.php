<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\QuickLink;
use App\Models\User;

class QuickLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function view(User $user, QuickLink $quickLink): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function update(User $user, QuickLink $quickLink): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    // No publish ability: is_active is a plain visibility flag, not an
    // editorial workflow state (QuickLink deliberately has no HasStatus).

    public function delete(User $user, QuickLink $quickLink): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
