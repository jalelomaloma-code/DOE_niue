<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasBlame;
use App\Models\Concerns\HasFeaturedImage;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Programme extends Model implements HasMedia
{
    use HasBlame, HasFactory, HasFeaturedImage, HasSeo, HasStatus, InteractsWithMedia {
        HasFeaturedImage::registerMediaCollections insteadof InteractsWithMedia;
        HasFeaturedImage::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'title', 'slug', 'summary', 'body', 'is_featured', 'sort_order',
        'status', 'published_at', 'seo_title', 'seo_description', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function seoFallbackDescription(): ?string
    {
        return $this->summary;
    }

    // Media collections and conversions come from HasFeaturedImage (Step 1b).
}
