<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function view(User $user, Document $document): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function update(User $user, Document $document): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function publish(User $user, Document $document): bool
    {
        return $user->mayPublish();
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
