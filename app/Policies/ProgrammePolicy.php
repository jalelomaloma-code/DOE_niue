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

    public function publish(User $user, Programme $programme): bool
    {
        return $user->mayPublish();
    }

    public function delete(User $user, Programme $programme): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
