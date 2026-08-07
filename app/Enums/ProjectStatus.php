<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::OnHold => 'On Hold',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Active => 'success',
            self::Completed => 'info',
            self::OnHold => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
