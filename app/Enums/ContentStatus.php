<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under Review',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::UnderReview => 'warning',
            self::Published => 'success',
            self::Archived => 'danger',
        };
    }

    /** @return array<string, string> value => label, for Filament select fields */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
