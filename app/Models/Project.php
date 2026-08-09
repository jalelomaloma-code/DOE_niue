<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ProjectStatus;
use App\Models\Concerns\HasBlame;
use App\Models\Concerns\HasFeaturedImage;
use App\Models\Concerns\HasSanitisedRichText;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use HasBlame, HasFactory, HasFeaturedImage, HasSanitisedRichText, HasSeo, HasStatus, InteractsWithMedia {
        HasFeaturedImage::registerMediaCollections insteadof InteractsWithMedia;
        HasFeaturedImage::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'title', 'slug', 'summary', 'body', 'programme_id', 'project_status',
        'start_date', 'end_date', 'is_featured', 'status', 'published_at',
        'seo_title', 'seo_description', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'project_status' => ProjectStatus::class,
            'published_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    /**
     * `body` is the only rich-text (unescaped-on-render) column here --
     * `summary` is a plain Textarea and renders escaped. Sanitising in the
     * model rather than in ProjectForm covers seeders, tinker, imports and
     * any future non-Filament writer, none of which go through a form.
     */
    protected function body(): Attribute
    {
        return Attribute::set(fn (?string $value) => $this->sanitiseRichText($value));
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function seoFallbackDescription(): ?string
    {
        return $this->summary;
    }

    // Media collections and conversions come from HasFeaturedImage.
}
