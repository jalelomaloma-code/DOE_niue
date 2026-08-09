<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TeamMember extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['name', 'role', 'bio', 'email', 'sort_order', 'is_active', 'is_demo'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_demo' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function photoUrl(string $conversion = 'card'): ?string
    {
        return $this->getFirstMediaUrl('photo', $conversion) ?: null;
    }

    public function photoAlt(): ?string
    {
        return $this->getFirstMedia('photo')?->getCustomProperty('alt');
    }

    public function registerMediaCollections(): void
    {
        // Disk pinned explicitly to 'public' -- see the comment in
        // HasFeaturedImage::registerMediaCollections() for why an unpinned
        // collection uploads fine through Filament but 403s on the front end.
        $this->addMediaCollection('photo')->singleFile()->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(600)->height(600)->nonQueued();
    }
}
