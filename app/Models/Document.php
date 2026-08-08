<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasBlame;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Document extends Model implements HasMedia
{
    use HasBlame, HasFactory, HasStatus, InteractsWithMedia;

    protected $fillable = [
        'title', 'slug', 'description', 'document_category_id',
        'published_date', 'is_featured', 'status', 'published_at', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'published_date' => 'date',
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia('file');
    }

    public function fileSizeForHumans(): ?string
    {
        $size = $this->file()?->size;

        return $size ? Number::fileSize($size, precision: 1) : null;
    }

    public function fileType(): ?string
    {
        $extension = $this->file()?->extension;

        return $extension ? strtoupper($extension) : null;
    }

    public function downloadUrl(): ?string
    {
        return $this->file()?->getUrl();
    }

    public function registerMediaCollections(): void
    {
        // See the comment in HasFeaturedImage::registerMediaCollections()
        // for why the disk must be pinned explicitly to 'public'.
        $this->addMediaCollection('file')
            ->singleFile()
            ->useDisk('public')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
