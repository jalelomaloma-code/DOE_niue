<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    public static function current(): self
    {
        // `firstOrCreate([])` inserts via `insertGetId`, which returns only
        // the new id — it never re-reads the row, so on the creation path
        // the in-memory model would see null instead of the DB-level column
        // defaults Postgres actually wrote. Refresh only when a row was just
        // created; an existing row was already loaded correctly by the
        // `where([])->first()` lookup inside `firstOrCreate`.
        $model = static::firstOrCreate([]);

        return $model->wasRecentlyCreated ? $model->fresh() : $model;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }
}
