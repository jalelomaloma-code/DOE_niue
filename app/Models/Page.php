<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasBlame;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Page extends Model
{
    use HasBlame, HasFactory, HasSeo, HasStatus;

    protected $fillable = [
        'title', 'slug', 'parent_id', 'intro', 'content', 'sort_order',
        'show_in_section_nav', 'status', 'published_at', 'seo_title',
        'seo_description', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'content' => 'array',
            'show_in_section_nav' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            $page->guardAgainstCyclicParent();
            $page->path = $page->computePath();
        });

        static::saved(function (Page $page): void {
            // Cascade on `path`, not `slug`/`parent_id`: a grandchild's slug
            // and parent_id never change when a grandparent is renamed, but
            // its computed path does, two levels down. Checking `path` is
            // what actually lets the cascade recurse past one generation.
            if ($page->wasChanged('path')) {
                $page->children()->get()->each->save();
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeSectionNav(Builder $query): Builder
    {
        return $query->where('show_in_section_nav', true);
    }

    public function seoFallbackDescription(): ?string
    {
        return $this->intro;
    }

    public function computePath(): string
    {
        $parent = $this->parent_id ? self::find($this->parent_id) : null;

        return $parent ? $parent->path.'/'.$this->slug : $this->slug;
    }

    /**
     * Refuse to save a page whose parent_id points at itself, or at any of
     * its own descendants.
     *
     * Without this, the `saved` hook's cascade (which re-saves children
     * whenever `path` changes) has no cycle detection: a page made its own
     * ancestor produces a longer `path` on every pass, so `wasChanged('path')`
     * never goes false and the cascade re-enters itself until the process
     * runs out of memory or `path` overflows its column. A brand-new page
     * (no id yet) cannot be its own ancestor, so this only walks the chain
     * for existing pages whose parent_id is actually changing hands.
     */
    protected function guardAgainstCyclicParent(): void
    {
        if ($this->parent_id === null || ! $this->exists) {
            return;
        }

        $ancestorId = $this->parent_id;
        $seen = [];

        while ($ancestorId !== null) {
            if ($ancestorId === $this->id) {
                throw new InvalidArgumentException(
                    $this->parent_id === $this->id
                        ? 'A page cannot be its own parent.'
                        : 'A page cannot be a descendant of itself.'
                );
            }

            // A pre-existing cycle elsewhere in the tree isn't this save's
            // problem to solve; stop walking rather than loop forever.
            if (isset($seen[$ancestorId])) {
                break;
            }
            $seen[$ancestorId] = true;

            $ancestorId = self::query()->whereKey($ancestorId)->value('parent_id');
        }
    }

    /**
     * Recompute and persist this page's path, cascading to descendants.
     *
     * Recomputation already happens automatically on every save() via the
     * saving/saved hooks above; this is a named entry point for callers
     * (e.g. an admin action or a data-repair command) that want to state
     * the intent explicitly without depending on that hook wiring.
     */
    public function refreshPath(): void
    {
        $this->save();
    }
}
