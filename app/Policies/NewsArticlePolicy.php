<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\NewsArticle;
use App\Models\User;

class NewsArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function view(User $user, NewsArticle $newsArticle): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function update(User $user, NewsArticle $newsArticle): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function publish(User $user, NewsArticle $newsArticle): bool
    {
        return $user->mayPublish();
    }

    public function delete(User $user, NewsArticle $newsArticle): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
