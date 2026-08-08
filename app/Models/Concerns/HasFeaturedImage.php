<?php

namespace App\Models\Concerns;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Featured and Open Graph imagery, shared by every model that appears as a
 * card or a hero. Conversion sizes live here so they change in one place.
 */
trait HasFeaturedImage
{
    public function registerMediaCollections(): void
    {
        // Disk pinned explicitly to 'public': the app's FILESYSTEM_DISK is
        // 'local' (Laravel 11+'s private, non-web-accessible disk), and
        // Filament's SpatieMediaLibraryFileUpload falls back to that app
        // default whenever a collection doesn't declare its own disk. An
        // unpinned collection here uploads fine through Filament but 404s/
        // 403s on the front end — the seeder never hits this because it
        // calls addMedia() directly, which resolves Spatie's own 'public'
        // default instead of Filament's.
        $this->addMediaCollection('featured_image')->singleFile()->useDisk('public');
        $this->addMediaCollection('og_image')->singleFile()->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(600)->height(400)->nonQueued();
        $this->addMediaConversion('hero')->width(1920)->height(900)->nonQueued();
    }

    public function featuredImageUrl(string $conversion = 'card'): ?string
    {
        return $this->getFirstMediaUrl('featured_image', $conversion) ?: null;
    }

    public function featuredImageAlt(): ?string
    {
        return $this->getFirstMedia('featured_image')?->getCustomProperty('alt');
    }
}
