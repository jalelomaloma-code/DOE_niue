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
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('og_image')->singleFile();
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
