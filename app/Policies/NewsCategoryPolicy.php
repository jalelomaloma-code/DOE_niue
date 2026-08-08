<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\NewsCategory;
use App\Models\User;

class NewsCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function view(User $user, NewsCategory $newsCategory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function update(User $user, NewsCategory $newsCategory): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    // No publish ability: categories have no editorial workflow.

    public function delete(User $user, NewsCategory $newsCategory): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
