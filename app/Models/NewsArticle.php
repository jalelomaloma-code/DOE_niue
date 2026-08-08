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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class NewsArticle extends Model implements HasMedia
{
    use HasBlame, HasFactory, HasFeaturedImage, HasSeo, HasStatus, InteractsWithMedia {
        HasFeaturedImage::registerMediaCollections insteadof InteractsWithMedia;
        HasFeaturedImage::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'news_category_id', 'author_name',
        'is_featured', 'status', 'published_at', 'share_to_facebook',
        'facebook_posted_at', 'facebook_post_id', 'seo_title', 'seo_description',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'share_to_facebook' => 'boolean',
            'facebook_posted_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    public function byline(): string
    {
        return $this->author_name ?: ($this->creator?->name ?? 'Department of Environment');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function seoFallbackDescription(): ?string
    {
        return $this->excerpt;
    }

    // Media collections and conversions come from HasFeaturedImage.
}
