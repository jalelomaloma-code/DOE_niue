<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

trait HasStatus
{
    /**
     * The ONLY sanctioned way to query content for public display.
     * Published, with a publish date that has actually arrived.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', ContentStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
