<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\QuickLink;
use App\Models\User;

/**
 * IMPORTANT — Filament allows by default when a model has no Policy at all.
 * Laravel's raw Gate denies with no policy registered, but
 * Filament\Resources\Resource\Concerns\HasAuthorization::getAuthorizationResponse()
 * returns Response::allow() when it finds none (panel "strict mode", which
 * would flip that default, is off and nothing in this codebase turns it on).
 * A Filament Resource with no matching {Model}Policy is therefore wide open
 * to every panel user, including Viewer — it does NOT inherit any
 * protection. Every new Resource needs its own Policy from day one; do not
 * assume the absence of one is safe just because other things are.
 */
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
