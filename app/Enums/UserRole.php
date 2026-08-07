<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case WebsiteManager = 'website_manager';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::WebsiteManager => 'Website Manager',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
        };
    }

    /** Roles permitted to publish content. Editors submit for review instead. */
    public function canPublish(): bool
    {
        return in_array($this, [self::SuperAdmin, self::WebsiteManager], true);
    }
}
