<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    /**
     * Mirrors the DB-level column defaults. `firstOrCreate([])` inserts via
     * `insertGetId`, which only returns the new id — it never re-reads the
     * row, so without these the in-memory model would see null instead of
     * the defaults Postgres actually wrote.
     */
    protected $attributes = [
        'department_name' => 'Niue Department of Environment',
        'government_name' => 'Government of Niue',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }
}
