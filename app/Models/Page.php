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

    /**
     * Slugs that would shadow, or be shadowed by, a real route.
     * Rejected at save time so an editor gets a validation message rather
     * than an unexplainable 404 six months later.
     */
    public const RESERVED_SLUGS = ['admin', 'storage', 'livewire', 'api', 'login', 'logout'];

    /**
     * Path prefixes the catch-all route refuses to match.
     *
     * The route's negative lookahead is prefix-based, not exact: `/admin` and
     * `/administration` are both excluded from the catch-all. RESERVED_SLUGS
     * above is an exact-match list, so on its own it lets an editor save
     * `administration`, see it listed as Published in the CMS, and get an
     * unlogged 404 on the site. PageForm validates against this constant with
     * str_starts_with(), and routes/web.php builds the route pattern from it,
     * so the two can no longer drift apart.
     */
    public const ROUTE_EXCLUDED_PREFIXES = ['admin', 'storage', 'livewire'];

    /**
     * The character set a single path segment may use, as a bare regex
     * fragment. Anything outside it (uppercase, underscores, spaces, accents)
     * fails the catch-all and 404s, so PageForm rejects it on the slug field.
     */
    public const SLUG_PATTERN = '[a-z0-9\-]+';

    /**
     * How many path segments the catch-all serves: a top-level page and one
     * level of child. A grandchild's computed `path` has three segments and
     * would 404, so PageForm refuses to create one.
     *
     * pathRoutePattern() below derives the number of optional segments from
     * this. PageForm's parent_id rules, however, encode depth 2 structurally
     * ("the parent must not itself have a parent", "a page with children must
     * stay top-level") — raising this constant would widen the route but NOT
     * the form, so revisit those two rules if it ever changes.
     */
    public const MAX_DEPTH = 2;

    /**
     * The catch-all route's `where` constraint, derived from the constants
     * above so that routes/web.php and PageForm's validation cannot disagree.
     */
    public static function pathRoutePattern(): string
    {
        $excluded = implode('|', self::ROUTE_EXCLUDED_PREFIXES);
        $segment = self::SLUG_PATTERN;
        $extraSegments = str_repeat("(\/{$segment})?", self::MAX_DEPTH - 1);

        return "^(?!{$excluded}){$segment}{$extraSegments}$";
    }

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
     * (no id yet) cannot be its own ancestor, so it is skipped; every other
     * save walks the chain. This is NOT gated on isDirty('parent_id') — a
     * save that leaves parent_id alone still walks, and so does each child
     * re-saved by the `saved` cascade, which makes a deep rename O(depth^2)
     * queries. Left unconditional deliberately: it is always safe, and at
     * two levels of nesting and a few dozen pages the cost is immaterial.
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

    // There is deliberately no refreshPath() helper. One existed, called by
    // nothing, whose whole body was $this->save() -- the saving/saved hooks
    // above already recompute and cascade `path` on every save, so it added a
    // second name for an operation that has one, and a name that implied it
    // did something save() does not.
}
