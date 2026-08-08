<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HomepageSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero_image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('hero')->width(1920)->height(900)->nonQueued();
    }
}
