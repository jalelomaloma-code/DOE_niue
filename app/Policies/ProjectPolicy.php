<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function view(User $user, Project $project): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function update(User $user, Project $project): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function publish(User $user, Project $project): bool
    {
        return $user->mayPublish();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
