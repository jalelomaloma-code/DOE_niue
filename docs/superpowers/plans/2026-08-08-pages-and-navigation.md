# Pages & Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give every navigation item a real page — a Department-editable block builder for narrative content, programme index and detail pages, a team model, and database-driven navigation.

**Architecture:** A `Page` model stores blocks as JSON and resolves by a unique `path` column computed from its slug and ancestry. Seven Blade partials — one per block — are dispatched by a single component, so adding a block never grows a central file. Rich text is sanitised against an allowlist on save, so the database never holds a payload. An explicit-routes-first, catch-all-last routing order lets the Department add pages without a developer, with reserved slugs rejected at save time.

**Tech Stack:** PHP 8.4, Laravel 13.24, Filament 5.7, Livewire 4, PostgreSQL 18, Tailwind 4, Pest 5, `symfony/html-sanitizer` 8.1.

**Spec:** `docs/superpowers/specs/2026-08-08-pages-and-navigation-design.md` (commit `276b439`)

---

## Global Constraints

Every task's requirements implicitly include this section.

- **PHP 8.4 floor.** Pest 5 requires `^8.4`. `symfony/html-sanitizer` v8.1.1 requires `>=8.4.1`.
- **Branch:** `feature/foundation-homepage`. **118 tests pass at HEAD `276b439` and must keep passing.**
- **`published()` is the only sanctioned public query path** — Published status AND non-null `published_at` AND not in the future. Never hand-write `where('status', ...)` outside the trait.
- **`'status' => ContentStatus::class` must be in the casts of any model using `HasStatus`.** `isPublished()` compares enum identity with no unwrapping safety net; without the cast it returns `false` forever, silently. **A test proving the cast must re-fetch from the database** — PHP backed enums are singletons, so an in-memory comparison passes either way.
- **Editors cannot publish.** Two halves: status-select option filtering (usability) and the `publish` policy ability (the control). Both.
- **Every Filament Resource needs a Policy.** Filament returns `Response::allow()` when no policy is registered — the opposite of Laravel's Gate. `tests/Feature/FilamentResourcePolicyTest.php` enforces this and will fail if you add a resource without one.
- **Alt text is required at upload time** on every image field, and **must hydrate on edit** via `->afterStateHydrated(fn ($component, $record) => $component->state($record?->featuredImageAlt()))` or every save of an image-bearing record fails validation.
- **Media collections must call `useDisk('public')`.** Without it, uploads land on the private `local` disk and 403 on the public site.
- **The yellow rule:** `#FCD116` is ~1.4:1 on white. Never text on white, never a button background with white text. Only a background carrying `#142B3A` text, an active indicator, a thin accent rule, or a small icon fill.
- **Exactly one `<h1>` per page.** Block headings are `<h2>`; content within a block is `<h3>`. Editors choose words, never levels.
- **No SPA framework on the public site.** No React, Vue, Inertia, Alpine. Livewire is confined to `/admin`.
- **PowerShell strips `^` from Composer constraints** via the `.bat` shim. After any `composer require`, verify `composer.json` kept the caret.
- **PATH is stale in every shell.** Prefix commands with `$env:Path = "C:\Users\jalel\.config\herd\bin;" + $env:Path`.
- **Never `git add -A`** — an untracked `.claude/settings.json` must not be committed. Stage explicitly by path.
- **Demo copy must not name real Niue institutions, statistics, dates or events**, and demo team members must be unmistakably fictional.
- **No deployment.** Local only.
- **Commits** use Conventional Commits ending with `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.
- **Filament generators are authoritative** wherever they differ from this plan's snippets. `Filament\Forms\Get` does not exist in 5.7 — it is `Filament\Schemas\Components\Utilities\Get`. Resources generate as a directory with separate `Schemas/` and `Tables/` classes.

---

## File Structure

```
app/
├── Enums/BlockType.php                        — the seven block types
├── Models/
│   ├── Page.php                               — blocks, hierarchy, path
│   ├── TeamMember.php
│   ├── NavigationItem.php
│   └── Concerns/HasSanitisedRichText.php      — save-time sanitisation
├── Support/RichTextSanitiser.php              — allowlist config + sanitise()
├── Http/Controllers/
│   ├── PageController.php                     — catch-all page resolution
│   └── ProgrammeController.php                — programme detail
├── Policies/{Page,TeamMember,NavigationItem}Policy.php
└── Filament/Resources/{Pages,TeamMembers,NavigationItems}/

resources/views/
├── components/
│   ├── page/content.blade.php                 — block dispatcher
│   └── blocks/
│       ├── rich-text.blade.php
│       ├── image.blade.php
│       ├── callout.blade.php
│       ├── card-grid.blade.php
│       ├── documents-list.blade.php
│       ├── programmes-list.blade.php
│       └── contact-details.blade.php
├── pages/show.blade.php                       — a Page
└── programmes/show.blade.php                  — a Programme

database/
├── migrations/                                — pages, team_members, navigation_items
└── seeders/{PageSeeder,TeamMemberSeeder,NavigationItemSeeder}.php

routes/web.php                                 — explicit first, catch-all last
```

Rationale: one file per block keeps each independently readable and testable, and means an eighth block is one schema plus one partial rather than an edit to a growing central file. Sanitisation is its own class so it can be tested without a model, and its own trait so any future model can adopt it.

---

## Task 1: Page model, migration and path computation

**Files:**
- Create: `database/migrations/xxxx_create_pages_table.php`, `app/Models/Page.php`, `database/factories/PageFactory.php`
- Test: `tests/Feature/PageTest.php`

**Interfaces:**
- Consumes: `HasStatus`, `HasBlame`, `HasSeo` from `app/Models/Concerns/`
- Produces: `Page` with `parent(): BelongsTo`, `children(): HasMany`, `scopePublished()`, `refreshPath(): void`, and a `path` column that later tasks route on

- [ ] **Step 1: Create the migration**

```powershell
$env:Path = "C:\Users\jalel\.config\herd\bin;" + $env:Path
php artisan make:migration create_pages_table
```

```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug');
    $table->string('path')->unique();
    $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
    $table->text('intro')->nullable();
    $table->json('content')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('show_in_section_nav')->default(true);
    $table->string('status')->default('draft')->index();
    $table->timestamp('published_at')->nullable()->index();
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->boolean('is_demo')->default(false)->index();
    $table->timestamps();

    $table->unique(['parent_id', 'slug']);
});
```

`path` is unique globally; `['parent_id', 'slug']` is unique so two children of the same parent cannot collide.

- [ ] **Step 2: Write the failing tests**

`tests/Feature/PageTest.php`:

```php
<?php

use App\Enums\ContentStatus;
use App\Models\Page;

it('computes a root path from the slug', function () {
    $page = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);

    expect($page->fresh()->path)->toBe('about');
});

it('computes a nested path from its ancestry', function () {
    $parent = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);
    $child = Page::factory()->create(['slug' => 'mandate', 'parent_id' => $parent->id]);

    expect($child->fresh()->path)->toBe('about/mandate');
});

it('recomputes descendant paths when a parent slug changes', function () {
    $parent = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);
    $child = Page::factory()->create(['slug' => 'mandate', 'parent_id' => $parent->id]);

    $parent->update(['slug' => 'about-us']);

    expect($child->fresh()->path)->toBe('about-us/mandate');
});

it('casts status so isPublished works on a freshly loaded record', function () {
    $page = Page::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $fresh = Page::findOrFail($page->id);

    expect($fresh->status)->toBe(ContentStatus::Published)
        ->and($fresh->isPublished())->toBeTrue();
});

it('excludes unpublished and future-dated pages from published()', function () {
    Page::factory()->create(['slug' => 'live', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['slug' => 'draft', 'status' => ContentStatus::Draft, 'published_at' => now()->subDay()]);
    Page::factory()->create(['slug' => 'later', 'status' => ContentStatus::Published, 'published_at' => now()->addWeek()]);

    expect(Page::published()->pluck('slug')->all())->toBe(['live']);
});
```

- [ ] **Step 3: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="Page"
```

Expected: FAIL with `Class "App\Models\Page" not found`.

- [ ] **Step 4: Create the model**

`app/Models/Page.php`:

```php
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
            $page->path = $page->computePath();
        });

        static::saved(function (Page $page): void {
            // A slug or parent change moves every descendant with it.
            if ($page->wasChanged(['slug', 'parent_id'])) {
                $page->children()->each->save();
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
}
```

- [ ] **Step 5: Create the factory**

`database/factories/PageFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'parent_id' => null,
            'intro' => fake()->sentence(12),
            'content' => [],
            'sort_order' => 0,
            'show_in_section_nav' => true,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'is_demo' => true,
        ];
    }
}
```

- [ ] **Step 6: Run the tests**

```powershell
php artisan migrate
./vendor/bin/pest --filter="Page"
```

Expected: all five PASS. If `recomputes descendant paths` fails, the `saved` hook is not re-saving children — check `wasChanged`, not `isDirty`, which is already false by then.

- [ ] **Step 7: Run the full suite and commit**

```powershell
./vendor/bin/pest
git add database/migrations app/Models/Page.php database/factories/PageFactory.php tests/Feature/PageTest.php
git commit -m "feat: page model with hierarchy and computed paths

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 2: Rich text sanitisation on save

**Files:**
- Create: `app/Support/RichTextSanitiser.php`, `app/Models/Concerns/HasSanitisedRichText.php`
- Modify: `composer.json`
- Test: `tests/Feature/RichTextSanitiserTest.php`

**Interfaces:**
- Consumes: nothing
- Produces: `RichTextSanitiser::sanitise(?string $html): ?string`, and a `HasSanitisedRichText` trait exposing `sanitiseRichText(?string $html): ?string` for models to call in mutators

This is the security-critical task in this plan. Spec 1 already found one stored-XSS path through a CMS-editable field.

- [ ] **Step 1: Require the sanitiser explicitly**

`symfony/html-sanitizer` v8.1.1 is already in `vendor/`, but only transitively via `filament/support`. If a future Filament release drops it, sanitisation disappears silently. Make it a direct dependency:

```powershell
$env:Path = "C:\Users\jalel\.config\herd\bin;" + $env:Path
composer require symfony/html-sanitizer:"^8.1"
```

**Then check `composer.json` kept the caret** — PowerShell has stripped it three times on this project. If it reads `"8.1"`, edit `composer.json` to `"^8.1"` and run `composer update --lock`.

- [ ] **Step 2: Write the failing tests**

`tests/Feature/RichTextSanitiserTest.php`:

```php
<?php

use App\Support\RichTextSanitiser;

it('strips a script tag', function () {
    $dirty = '<p>Hello</p><script>alert(1)</script>';

    expect(RichTextSanitiser::sanitise($dirty))->not->toContain('script')
        ->and(RichTextSanitiser::sanitise($dirty))->toContain('Hello');
});

it('strips an inline event handler', function () {
    $dirty = '<p onclick="alert(1)">Hello</p>';

    expect(RichTextSanitiser::sanitise($dirty))->not->toContain('onclick');
});

it('strips a javascript href but keeps the link text', function () {
    $dirty = '<a href="javascript:alert(1)">Click</a>';
    $clean = RichTextSanitiser::sanitise($dirty);

    expect($clean)->not->toContain('javascript:')
        ->and($clean)->toContain('Click');
});

it('keeps legitimate formatting and links', function () {
    $clean = RichTextSanitiser::sanitise(
        '<h2>Waste</h2><p><strong>Bold</strong> and <em>italic</em></p>'
        .'<ul><li>One</li></ul><a href="/waste-and-recycling">Local</a>'
        .'<a href="https://gov.nu">External</a>'
    );

    expect($clean)->toContain('<h2>')
        ->and($clean)->toContain('<strong>')
        ->and($clean)->toContain('<li>')
        ->and($clean)->toContain('/waste-and-recycling')
        ->and($clean)->toContain('https://gov.nu');
});

it('returns null unchanged', function () {
    expect(RichTextSanitiser::sanitise(null))->toBeNull();
});
```

- [ ] **Step 3: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="RichTextSanitiser"
```

Expected: FAIL with `Class "App\Support\RichTextSanitiser" not found`.

- [ ] **Step 4: Implement the sanitiser**

`app/Support/RichTextSanitiser.php`:

```php
<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitises CMS rich text ON SAVE, so the database never holds a payload.
 *
 * Filament registers its own HtmlSanitizerConfig and sanitises on RENDER
 * (see vendor/filament/support/src/SupportServiceProvider.php). That protects
 * Filament's own output, but leaves whatever was pasted sitting in the
 * database and depends on every future render path remembering to clean it.
 * The public site renders this HTML unescaped, so we clean it before storage.
 *
 * Allowlist, not blocklist: blocklists lose to novel payloads.
 */
class RichTextSanitiser
{
    public static function sanitise(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return (new HtmlSanitizer(self::config()))->sanitize($html);
    }

    private static function config(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowAttribute('class', allowedElements: '*');
    }
}
```

`allowSafeElements()` is Symfony's W3C-derived safe set — headings, paragraphs, lists, `strong`, `em`, `blockquote`, `a`. `allowLinkSchemes` is what rejects `javascript:` and `data:`. `style` is deliberately **not** allowed, unlike Filament's own render-time config: inline styles let an editor override the design system and are not needed for the block set.

- [ ] **Step 5: Create the trait**

`app/Models/Concerns/HasSanitisedRichText.php`:

```php
<?php

namespace App\Models\Concerns;

use App\Support\RichTextSanitiser;

trait HasSanitisedRichText
{
    public function sanitiseRichText(?string $html): ?string
    {
        return RichTextSanitiser::sanitise($html);
    }
}
```

- [ ] **Step 6: Run the tests**

```powershell
./vendor/bin/pest --filter="RichTextSanitiser"
```

Expected: all five PASS.

- [ ] **Step 7: Prove it is load-bearing**

Temporarily change `allowLinkSchemes(['http', 'https', 'mailto'])` to include `'javascript'`, re-run, and confirm the `javascript href` test fails. Restore. Record the output in your report — a sanitiser that cannot be shown to reject something is not evidence of anything.

- [ ] **Step 8: Run the full suite and commit**

```powershell
./vendor/bin/pest
git add composer.json composer.lock app/Support/RichTextSanitiser.php app/Models/Concerns/HasSanitisedRichText.php tests/Feature/RichTextSanitiserTest.php
git commit -m "feat: allowlist rich-text sanitisation on save

symfony/html-sanitizer was present only transitively via filament/support;
required explicitly so a future Filament release cannot silently remove it.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 3: Block rendering architecture and the rich text block

**Files:**
- Create: `app/Enums/BlockType.php`, `resources/views/components/page/content.blade.php`, `resources/views/components/blocks/rich-text.blade.php`
- Test: `tests/Feature/BlockRenderingTest.php`

**Interfaces:**
- Consumes: `Page::$content` (array)
- Produces: `<x-page.content :blocks="$page->content" />`, and `BlockType` enum whose `value` matches the `type` key stored in the JSON

- [ ] **Step 1: Create the block type enum**

`app/Enums/BlockType.php`:

```php
<?php

namespace App\Enums;

enum BlockType: string
{
    case RichText = 'rich_text';
    case Image = 'image';
    case Callout = 'callout';
    case CardGrid = 'card_grid';
    case DocumentsList = 'documents_list';
    case ProgrammesList = 'programmes_list';
    case ContactDetails = 'contact_details';

    public function label(): string
    {
        return match ($this) {
            self::RichText => 'Text',
            self::Image => 'Image',
            self::Callout => 'Callout',
            self::CardGrid => 'Card grid',
            self::DocumentsList => 'Documents list',
            self::ProgrammesList => 'Programmes list',
            self::ContactDetails => 'Contact details',
        };
    }

    /** The Blade component rendering this block. */
    public function view(): string
    {
        return 'components.blocks.'.str_replace('_', '-', $this->value);
    }
}
```

- [ ] **Step 2: Write the failing tests**

`tests/Feature/BlockRenderingTest.php`:

```php
<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

it('renders a rich text block', function () {
    $html = Blade::render(
        '<x-page.content :blocks="$blocks" />',
        ['blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<p>Waste services</p>']],
        ]]
    );

    expect($html)->toContain('Waste services');
});

it('skips an unknown block type without fataling', function () {
    Log::spy();

    $html = Blade::render(
        '<x-page.content :blocks="$blocks" />',
        ['blocks' => [
            ['type' => 'no_such_block', 'data' => []],
            ['type' => 'rich_text', 'data' => ['body' => '<p>Still here</p>']],
        ]]
    );

    expect($html)->toContain('Still here');
    Log::shouldHaveReceived('warning')->once();
});

it('renders nothing for an empty block list', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => []]);

    expect(trim($html))->toBe('');
});

it('renders nothing when blocks are null', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => null]);

    expect(trim($html))->toBe('');
});
```

- [ ] **Step 3: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="Block"
```

Expected: FAIL — `Unable to locate a class or view for component [page.content]`.

- [ ] **Step 4: Build the dispatcher**

`resources/views/components/page/content.blade.php`:

```blade
@props(['blocks' => []])

@php
    use App\Enums\BlockType;
@endphp

@foreach (($blocks ?? []) as $block)
    @php
        $type = BlockType::tryFrom($block['type'] ?? '');

        // A block type removed from the code while pages still hold it in
        // their JSON must not take the page down. Losing one section is
        // recoverable; a 500 on a live government page is not.
        if (! $type) {
            \Illuminate\Support\Facades\Log::warning('Unknown page block type', [
                'type' => $block['type'] ?? null,
            ]);
        }
    @endphp

    @if ($type)
        @include($type->view(), ['data' => $block['data'] ?? []])
    @endif
@endforeach
```

- [ ] **Step 5: Build the rich text block**

`resources/views/components/blocks/rich-text.blade.php`:

```blade
@php($heading = $data['heading'] ?? null)

<section class="mx-auto max-w-3xl px-4 py-8">
    @if ($heading)
        <h2 class="mb-4 text-2xl font-bold text-brand">{{ $heading }}</h2>
    @endif

    {{-- Sanitised on save by RichTextSanitiser; never render unsanitised input. --}}
    <div class="prose prose-slate max-w-none">
        {!! $data['body'] ?? '' !!}
    </div>
</section>
```

- [ ] **Step 6: Run the tests**

```powershell
./vendor/bin/pest --filter="Block"
```

Expected: all four PASS.

- [ ] **Step 7: Commit**

```powershell
./vendor/bin/pest
git add app/Enums/BlockType.php resources/views/components/page resources/views/components/blocks tests/Feature/BlockRenderingTest.php
git commit -m "feat: block rendering architecture with graceful unknown-type handling

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 4: Static blocks — image, callout, card grid

**Files:**
- Create: `resources/views/components/blocks/{image,callout,card-grid}.blade.php`
- Modify: `tests/Feature/BlockRenderingTest.php`

**Interfaces:**
- Consumes: `BlockType` and the dispatcher from Task 3
- Produces: three renderable block views

Block data shapes, which Task 6's Filament builder must match exactly:

| Block | `data` keys |
|---|---|
| `image` | `url` (string), `alt` (string, required), `caption` (nullable string) |
| `callout` | `heading` (nullable), `body` (string), `tone` (`info` \| `warning`) |
| `card_grid` | `heading` (nullable), `cards` (array of `title`, `text`, `url` nullable) |

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/BlockRenderingTest.php`:

```php
it('renders an image block with its alt text and caption', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'image', 'data' => [
            'url' => '/storage/demo.jpg',
            'alt' => 'Coastline at dawn',
            'caption' => 'The northern coast',
        ]],
    ]]);

    expect($html)->toContain('alt="Coastline at dawn"')
        ->and($html)->toContain('The northern coast');
});

it('renders a callout with its tone', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'callout', 'data' => ['heading' => 'Note', 'body' => 'Collection changes', 'tone' => 'warning']],
    ]]);

    expect($html)->toContain('Note')->and($html)->toContain('Collection changes');
});

it('renders a card grid and omits links for cards without a url', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'card_grid', 'data' => ['heading' => 'Services', 'cards' => [
            ['title' => 'Household', 'text' => 'Weekly collection', 'url' => '/waste-and-recycling'],
            ['title' => 'Green waste', 'text' => 'Monthly collection', 'url' => null],
        ]]],
    ]]);

    expect($html)->toContain('Household')
        ->and($html)->toContain('/waste-and-recycling')
        ->and($html)->toContain('Green waste');
});

it('renders an image block without a caption', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'image', 'data' => ['url' => '/storage/demo.jpg', 'alt' => 'Reef']],
    ]]);

    expect($html)->toContain('alt="Reef"');
});
```

- [ ] **Step 2: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="Block"
```

Expected: FAIL — the four new tests error on missing views.

- [ ] **Step 3: Build the image block**

`resources/views/components/blocks/image.blade.php`:

```blade
<figure class="mx-auto max-w-4xl px-4 py-8">
    <img src="{{ $data['url'] ?? '' }}"
         alt="{{ $data['alt'] ?? '' }}"
         class="w-full rounded" loading="lazy">

    @if (! empty($data['caption']))
        <figcaption class="mt-2 text-sm text-text/70">{{ $data['caption'] }}</figcaption>
    @endif
</figure>
```

- [ ] **Step 4: Build the callout block**

`resources/views/components/blocks/callout.blade.php`:

```blade
@php
    // Accent is a background carrying ink text — never text on white.
    $tone = ($data['tone'] ?? 'info') === 'warning'
        ? 'bg-accent text-ink'
        : 'bg-brand/5 text-text border-l-4 border-brand';
@endphp

<aside class="mx-auto max-w-3xl px-4 py-8">
    <div class="rounded p-6 {{ $tone }}">
        @if (! empty($data['heading']))
            <h2 class="mb-2 text-lg font-bold">{{ $data['heading'] }}</h2>
        @endif

        <p>{{ $data['body'] ?? '' }}</p>
    </div>
</aside>
```

- [ ] **Step 5: Build the card grid block**

`resources/views/components/blocks/card-grid.blade.php`:

```blade
<section class="mx-auto max-w-7xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (($data['cards'] ?? []) as $card)
            <li class="rounded border border-black/10 bg-white p-6">
                <h3 class="font-semibold text-brand">
                    @if (! empty($card['url']))
                        <a href="{{ $card['url'] }}" class="hover:underline">{{ $card['title'] ?? '' }}</a>
                    @else
                        {{ $card['title'] ?? '' }}
                    @endif
                </h3>

                @if (! empty($card['text']))
                    <p class="mt-2 text-sm">{{ $card['text'] }}</p>
                @endif
            </li>
        @endforeach
    </ul>
</section>
```

- [ ] **Step 6: Run the tests and commit**

```powershell
./vendor/bin/pest --filter="Block"
./vendor/bin/pest
git add resources/views/components/blocks tests/Feature/BlockRenderingTest.php
git commit -m "feat: image, callout and card grid blocks

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 5: Dynamic blocks — documents, programmes, contact details

**Files:**
- Create: `resources/views/components/blocks/{documents-list,programmes-list,contact-details}.blade.php`
- Modify: `tests/Feature/BlockRenderingTest.php`

**Interfaces:**
- Consumes: `Document::published()`, `Programme::published()`, `SiteSetting::current()`
- Produces: three blocks that query live records

Data shapes:

| Block | `data` keys |
|---|---|
| `documents_list` | `heading` (nullable), `category_id` (nullable — all categories when null), `limit` (int, default 10) |
| `programmes_list` | `heading` (nullable), `limit` (int, default 12) |
| `contact_details` | `heading` (nullable) |

**These blocks must use `published()`.** They render on the public site.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/BlockRenderingTest.php`:

```php
use App\Enums\ContentStatus;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Programme;
use App\Models\SiteSetting;

it('lists only published documents', function () {
    $category = DocumentCategory::create(['name' => 'Forms', 'slug' => 'forms', 'sort_order' => 0]);

    Document::factory()->create([
        'title' => 'Visible Form',
        'document_category_id' => $category->id,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    Document::factory()->create([
        'title' => 'Draft Form',
        'document_category_id' => $category->id,
        'status' => ContentStatus::Draft,
    ]);

    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'documents_list', 'data' => ['heading' => 'Forms', 'category_id' => $category->id]],
    ]]);

    expect($html)->toContain('Visible Form')->and($html)->not->toContain('Draft Form');
});

it('lists only published programmes', function () {
    Programme::factory()->create(['title' => 'Live Programme', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Programme::factory()->create(['title' => 'Hidden Programme', 'status' => ContentStatus::Draft]);

    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'programmes_list', 'data' => []],
    ]]);

    expect($html)->toContain('Live Programme')->and($html)->not->toContain('Hidden Programme');
});

it('renders contact details from site settings', function () {
    SiteSetting::current()->update(['email' => 'environment@mail.gov.nu', 'address' => 'Alofi, Niue']);

    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'contact_details', 'data' => ['heading' => 'Contact']],
    ]]);

    expect($html)->toContain('environment@mail.gov.nu')->and($html)->toContain('Alofi, Niue');
});

it('renders a documents block with no matching documents without error', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'documents_list', 'data' => ['heading' => 'Empty']],
    ]]);

    expect($html)->toContain('Empty');
});
```

- [ ] **Step 2: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="Block"
```

Expected: FAIL on missing views.

- [ ] **Step 3: Build the documents list block**

`resources/views/components/blocks/documents-list.blade.php`:

```blade
@php
    $documents = \App\Models\Document::published()
        ->with('category')
        ->when(! empty($data['category_id']), fn ($q) => $q->where('document_category_id', $data['category_id']))
        ->latest('published_date')
        ->take($data['limit'] ?? 10)
        ->get();
@endphp

<section class="mx-auto max-w-7xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    @if ($documents->isNotEmpty())
        <ul class="divide-y divide-black/10">
            @foreach ($documents as $document)
                <x-content.document-row :document="$document" />
            @endforeach
        </ul>
    @endif
</section>
```

Reuses `<x-content.document-row>` built in Spec 1 — same markup as the homepage's resources section, so file type, size and download button behave identically.

- [ ] **Step 4: Build the programmes list block**

`resources/views/components/blocks/programmes-list.blade.php`:

```blade
@php
    $programmes = \App\Models\Programme::published()
        ->with('media')
        ->orderBy('sort_order')
        ->take($data['limit'] ?? 12)
        ->get();
@endphp

<section class="mx-auto max-w-7xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    @if ($programmes->isNotEmpty())
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($programmes as $programme)
                <x-content.programme-card :programme="$programme" />
            @endforeach
        </div>
    @endif
</section>
```

Note `->with('media')` — Spec 1's homepage shipped an N+1 here and it was measured at 12 extra queries. Do not repeat it.

- [ ] **Step 5: Build the contact details block**

`resources/views/components/blocks/contact-details.blade.php`:

```blade
@php($settings = \App\Models\SiteSetting::current())

<section class="mx-auto max-w-3xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    <dl class="space-y-4">
        @if ($settings->address)
            <div><dt class="font-semibold">Address</dt><dd>{{ $settings->address }}</dd></div>
        @endif
        @if ($settings->phone)
            <div><dt class="font-semibold">Phone</dt>
                <dd><a class="inline-flex min-h-11 items-center text-brand hover:underline" href="tel:{{ $settings->phone }}">{{ $settings->phone }}</a></dd></div>
        @endif
        @if ($settings->email)
            <div><dt class="font-semibold">Email</dt>
                <dd><a class="inline-flex min-h-11 items-center text-brand hover:underline" href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></dd></div>
        @endif
        @if ($settings->office_hours)
            <div><dt class="font-semibold">Office hours</dt><dd>{{ $settings->office_hours }}</dd></div>
        @endif
    </dl>
</section>
```

- [ ] **Step 6: Run the tests and commit**

```powershell
./vendor/bin/pest --filter="Block"
./vendor/bin/pest
git add resources/views/components/blocks tests/Feature/BlockRenderingTest.php
git commit -m "feat: documents, programmes and contact detail blocks

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 6: Page Filament resource with reserved-slug validation

**Files:**
- Create: `app/Policies/PagePolicy.php`, `app/Filament/Resources/Pages/**`
- Test: `tests/Feature/PageResourceTest.php`

**Interfaces:**
- Consumes: `Page`, `BlockType`, `RichTextSanitiser`
- Produces: a working Pages CMS resource; `Page::RESERVED_SLUGS` constant

- [ ] **Step 1: Add reserved slugs to the model**

Add to `app/Models/Page.php`:

```php
/**
 * Slugs that would shadow, or be shadowed by, a real route.
 * Rejected at save time so an editor gets a validation message rather
 * than an unexplainable 404 six months later.
 */
public const RESERVED_SLUGS = ['admin', 'storage', 'livewire', 'api', 'login', 'logout'];
```

- [ ] **Step 2: Write the failing tests**

`tests/Feature/PageResourceTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function pageUser(UserRole $role): User
{
    foreach (UserRole::cases() as $case) {
        Role::findOrCreate($case->value);
    }

    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

it('rejects a reserved slug', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Admin', 'slug' => 'admin'])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    expect(Page::where('slug', 'admin')->exists())->toBeFalse();
});

it('accepts a normal slug', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'About Us', 'slug' => 'about'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::where('slug', 'about')->exists())->toBeTrue();
});

it('refuses page creation to a viewer', function () {
    $this->actingAs(pageUser(UserRole::Viewer));

    $this->get(CreatePage::getUrl())->assertForbidden();
});

it('hides the published option from an editor', function () {
    $this->actingAs(pageUser(UserRole::Editor));

    Livewire::test(CreatePage::class)
        ->assertFormFieldExists('status', function ($field) {
            return ! array_key_exists('published', $field->getOptions());
        });
});
```

- [ ] **Step 3: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="PageResource"
```

Expected: FAIL — resource classes do not exist.

- [ ] **Step 4: Create the policy**

`app/Policies/PagePolicy.php` — copy `app/Policies/DocumentPolicy.php` exactly, substituting `Page` for `Document`. It already has the right shape: `viewAny`/`view` for any role, `create`/`update` blocked for Viewer, `publish` gated on `mayPublish()`, `delete` restricted to Super Admin and Website Manager.

- [ ] **Step 5: Generate the resource**

```powershell
php artisan make:filament-resource Page --generate --record-title-attribute=title -n
```

Then edit `app/Filament/Resources/Pages/Schemas/PageForm.php` to contain:

```php
TextInput::make('title')->required()->maxLength(255)
    ->live(onBlur: true)
    ->afterStateUpdated(fn ($state, $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

TextInput::make('slug')->required()->maxLength(255)
    ->unique(ignoreRecord: true)
    ->rule(fn () => function (string $attribute, $value, $fail) {
        if (in_array($value, \App\Models\Page::RESERVED_SLUGS, true)) {
            $fail("The slug \"{$value}\" is reserved and would conflict with a system route.");
        }
    }),

Select::make('parent_id')->relationship('parent', 'title')->searchable()->nullable(),

Textarea::make('intro')->rows(2)->maxLength(300)
    ->helperText('One or two sentences. Shown under the title and in section listings.'),

Builder::make('content')->blocks([
    Builder\Block::make('rich_text')->label('Text')->schema([
        TextInput::make('heading')->maxLength(255),
        RichEditor::make('body')->required()
            ->dehydrateStateUsing(fn (?string $state) => \App\Support\RichTextSanitiser::sanitise($state)),
    ]),
    Builder\Block::make('image')->schema([
        FileUpload::make('url')->image()->required()->disk('public')->directory('page-images')->maxSize(5120),
        TextInput::make('alt')->required()->maxLength(255)
            ->helperText('Describe the image for screen readers. Required.'),
        TextInput::make('caption')->maxLength(255),
    ]),
    Builder\Block::make('callout')->schema([
        TextInput::make('heading')->maxLength(255),
        Textarea::make('body')->required()->rows(3),
        Select::make('tone')->options(['info' => 'Information', 'warning' => 'Warning'])->default('info')->required(),
    ]),
    Builder\Block::make('card_grid')->label('Card grid')->schema([
        TextInput::make('heading')->maxLength(255),
        Repeater::make('cards')->schema([
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('text')->rows(2),
            TextInput::make('url')->maxLength(255)
                ->regex('/^(\/(?!\/)\S*|https?:\/\/\S+)$/')
                ->helperText('A site-relative path, e.g. /waste-and-recycling'),
        ])->minItems(1),
    ]),
    Builder\Block::make('documents_list')->label('Documents list')->schema([
        TextInput::make('heading')->maxLength(255),
        Select::make('category_id')->label('Category')
            ->relationship('', '')  // replaced below — see note
            ->options(fn () => \App\Models\DocumentCategory::orderBy('sort_order')->pluck('name', 'id'))
            ->helperText('Leave blank to list documents from every category.'),
        TextInput::make('limit')->numeric()->default(10)->minValue(1)->maxValue(50),
    ]),
    Builder\Block::make('programmes_list')->label('Programmes list')->schema([
        TextInput::make('heading')->maxLength(255),
        TextInput::make('limit')->numeric()->default(12)->minValue(1)->maxValue(50),
    ]),
    Builder\Block::make('contact_details')->label('Contact details')->schema([
        TextInput::make('heading')->maxLength(255),
    ]),
])->collapsible()->blockNumbers(false),

Toggle::make('show_in_section_nav')->default(true)
    ->helperText('Show this page in its section listing. Does not affect the main menu.'),

Select::make('status')
    ->options(function (): array {
        $options = \App\Enums\ContentStatus::options();

        if (! (auth()->user()?->mayPublish() ?? false)) {
            unset($options[\App\Enums\ContentStatus::Published->value]);
        }

        return $options;
    })
    ->default(\App\Enums\ContentStatus::Draft->value)
    ->required(),

DateTimePicker::make('published_at'),
TextInput::make('seo_title')->maxLength(255),
Textarea::make('seo_description')->rows(2)->maxLength(300),
```

**Note on `documents_list.category_id`:** drop the stray `->relationship('', '')` line — it is a generator artefact. `->options(...)` alone is correct, because a Builder block is not a relation.

`->dehydrateStateUsing()` on the RichEditor is what enforces save-time sanitisation. Verify it actually fires — see Step 7.

- [ ] **Step 6: Run the tests**

```powershell
php artisan migrate
./vendor/bin/pest --filter="PageResource"
```

Expected: all four PASS.

- [ ] **Step 7: Prove sanitisation fires through the form**

Add to `tests/Feature/PageResourceTest.php`:

```php
it('strips a script tag from rich text before it reaches the database', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Waste',
            'slug' => 'waste',
            'content' => [
                ['type' => 'rich_text', 'data' => ['body' => '<p>Safe</p><script>alert(1)</script>']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $stored = json_encode(Page::where('slug', 'waste')->first()->content);

    expect($stored)->not->toContain('script')->and($stored)->toContain('Safe');
});
```

Run it, then temporarily remove the `->dehydrateStateUsing(...)` call and confirm the test fails. Restore. **This is the test that proves the whole sanitisation chain, not just the sanitiser class in isolation.**

- [ ] **Step 8: Commit**

```powershell
./vendor/bin/pest
git add app/Models/Page.php app/Policies/PagePolicy.php app/Filament/Resources/Pages tests/Feature/PageResourceTest.php
git commit -m "feat: pages CMS resource with block builder and reserved-slug validation

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 7: Page routing and section landing pages

**Files:**
- Create: `app/Http/Controllers/PageController.php`, `resources/views/pages/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PageRoutingTest.php`

**Interfaces:**
- Consumes: `Page::published()`, `<x-page.content>`
- Produces: the public page route

- [ ] **Step 1: Write the failing tests**

`tests/Feature/PageRoutingTest.php`:

```php
<?php

use App\Enums\ContentStatus;
use App\Models\Page;

it('serves a published root page', function () {
    Page::factory()->create(['title' => 'About Us', 'slug' => 'about', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);

    $this->withoutVite()->get('/about')->assertOk()->assertSee('About Us');
});

it('serves a published child page at its nested path', function () {
    $parent = Page::factory()->create(['slug' => 'about', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Mandate', 'slug' => 'mandate', 'parent_id' => $parent->id, 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);

    $this->withoutVite()->get('/about/mandate')->assertOk()->assertSee('Mandate');
});

it('404s an unpublished page', function () {
    Page::factory()->create(['slug' => 'secret', 'status' => ContentStatus::Draft]);

    $this->withoutVite()->get('/secret')->assertNotFound();
});

it('404s a future-dated page', function () {
    Page::factory()->create(['slug' => 'soon', 'status' => ContentStatus::Published, 'published_at' => now()->addWeek()]);

    $this->withoutVite()->get('/soon')->assertNotFound();
});

it('does not shadow the admin panel', function () {
    $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
});

it('lists published children on a section landing page', function () {
    $parent = Page::factory()->create(['title' => 'About Us', 'slug' => 'about', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Mandate', 'slug' => 'mandate', 'parent_id' => $parent->id, 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Hidden Child', 'slug' => 'hidden', 'parent_id' => $parent->id, 'status' => ContentStatus::Draft]);

    $response = $this->withoutVite()->get('/about');

    $response->assertSee('Mandate');
    $response->assertDontSee('Hidden Child');
});
```

- [ ] **Step 2: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="PageRouting"
```

Expected: FAIL — 404 on `/about`.

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/PageController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(string $path): View
    {
        $page = Page::published()->where('path', $path)->firstOrFail();

        return view('pages.show', [
            'page' => $page,
            'children' => $page->children()->published()->sectionNav()->get(),
            'siblings' => $page->parent_id
                ? Page::published()->sectionNav()->where('parent_id', $page->parent_id)->orderBy('sort_order')->get()
                : collect(),
        ]);
    }
}
```

- [ ] **Step 4: Register the route LAST**

Append to `routes/web.php`, after every other route:

```php
/*
 * Page catch-all. MUST be registered last — it matches any path.
 * /admin, /storage and /livewire are excluded by pattern; reserved slugs
 * are additionally rejected at CMS save time (Page::RESERVED_SLUGS).
 */
Route::get('/{path}', [\App\Http\Controllers\PageController::class, 'show'])
    ->where('path', '^(?!admin|storage|livewire)[a-z0-9\-]+(\/[a-z0-9\-]+)?$')
    ->name('pages.show');
```

- [ ] **Step 5: Build the page view**

`resources/views/pages/show.blade.php`:

```blade
<x-layouts.public :title="$page->seoTitle()" :description="$page->seoDescription()">
    <article>
        <header class="bg-surface">
            <div class="mx-auto max-w-3xl px-4 py-12">
                <h1 class="text-3xl font-bold text-brand sm:text-4xl">{{ $page->title }}</h1>

                @if ($page->intro)
                    <p class="mt-4 text-lg">{{ $page->intro }}</p>
                @endif
            </div>
        </header>

        <x-page.content :blocks="$page->content" />

        @if ($children->isNotEmpty())
            <nav aria-label="In this section" class="mx-auto max-w-3xl px-4 py-8">
                <h2 class="mb-4 text-2xl font-bold text-brand">In this section</h2>
                <ul class="space-y-2">
                    @foreach ($children as $child)
                        <li>
                            <a href="/{{ $child->path }}"
                               class="inline-flex min-h-11 items-center text-brand hover:underline">
                                {{ $child->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($siblings->count() > 1)
            <nav aria-label="Related pages" class="mx-auto max-w-3xl px-4 pb-12">
                <ul class="flex flex-wrap gap-4">
                    @foreach ($siblings as $sibling)
                        <li>
                            <a href="/{{ $sibling->path }}"
                               @if ($sibling->is($page)) aria-current="page" @endif
                               @class([
                                   'inline-flex min-h-11 items-center border-b-2 px-1 text-sm font-semibold',
                                   'border-accent text-ink' => $sibling->is($page),
                                   'border-transparent text-brand hover:border-brand' => ! $sibling->is($page),
                               ])>
                                {{ $sibling->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </article>
</x-layouts.public>
```

- [ ] **Step 6: Run the tests and commit**

```powershell
./vendor/bin/pest --filter="PageRouting"
./vendor/bin/pest
git add app/Http/Controllers/PageController.php resources/views/pages routes/web.php tests/Feature/PageRoutingTest.php
git commit -m "feat: page routing with catch-all and section landing pages

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 8: TeamMember model, resource and rendering

**Files:**
- Create: migration, `app/Models/TeamMember.php`, factory, `app/Policies/TeamMemberPolicy.php`, `app/Filament/Resources/TeamMembers/**`, `resources/views/components/blocks/team-grid.blade.php`
- Modify: `app/Enums/BlockType.php`
- Test: `tests/Feature/TeamMemberTest.php`

**Interfaces:**
- Consumes: medialibrary
- Produces: `TeamMember::active()`, and a `team_grid` block type

This adds an eighth block. It is the one the spec's Our Team page needs, and it renders a model rather than manual cards.

- [ ] **Step 1: Create the migration**

```php
Schema::create('team_members', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('role');
    $table->text('bio')->nullable();
    $table->string('email')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true)->index();
    $table->boolean('is_demo')->default(false)->index();
    $table->timestamps();
});
```

- [ ] **Step 2: Write the failing tests**

`tests/Feature/TeamMemberTest.php`:

```php
<?php

use App\Models\TeamMember;

it('returns active members in sort order', function () {
    TeamMember::create(['name' => 'Second', 'role' => 'Officer', 'sort_order' => 2]);
    TeamMember::create(['name' => 'First', 'role' => 'Director', 'sort_order' => 1]);
    TeamMember::create(['name' => 'Hidden', 'role' => 'Officer', 'sort_order' => 0, 'is_active' => false]);

    expect(TeamMember::active()->pluck('name')->all())->toBe(['First', 'Second']);
});

it('renders a team grid block with active members only', function () {
    TeamMember::create(['name' => 'Visible Person', 'role' => 'Director', 'sort_order' => 1]);
    TeamMember::create(['name' => 'Former Person', 'role' => 'Officer', 'sort_order' => 2, 'is_active' => false]);

    $html = \Illuminate\Support\Facades\Blade::render(
        '<x-page.content :blocks="$blocks" />',
        ['blocks' => [['type' => 'team_grid', 'data' => ['heading' => 'Our Team']]]]
    );

    expect($html)->toContain('Visible Person')
        ->and($html)->toContain('Director')
        ->and($html)->not->toContain('Former Person');
});
```

- [ ] **Step 3: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="TeamMember"
```

Expected: FAIL — model not found.

- [ ] **Step 4: Create the model**

`app/Models/TeamMember.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TeamMember extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['name', 'role', 'bio', 'email', 'sort_order', 'is_active', 'is_demo'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_demo' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function photoUrl(string $conversion = 'card'): ?string
    {
        return $this->getFirstMediaUrl('photo', $conversion) ?: null;
    }

    public function photoAlt(): ?string
    {
        return $this->getFirstMedia('photo')?->getCustomProperty('alt');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile()->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(600)->height(600)->nonQueued();
    }
}
```

`useDisk('public')` is mandatory — without it uploads 403 on the public site, as Spec 1 discovered across all four content models.

- [ ] **Step 5: Add the block type**

Add to `app/Enums/BlockType.php`:

```php
case TeamGrid = 'team_grid';
```

and to both `match` expressions: `self::TeamGrid => 'Team grid',`.

- [ ] **Step 6: Build the block view**

`resources/views/components/blocks/team-grid.blade.php`:

```blade
@php($members = \App\Models\TeamMember::active()->with('media')->get())

<section class="mx-auto max-w-7xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    @if ($members->isNotEmpty())
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($members as $member)
                <li class="rounded border border-black/10 bg-white p-6 text-center">
                    @if ($url = $member->photoUrl())
                        <img src="{{ $url }}" alt="{{ $member->photoAlt() ?? $member->name }}"
                             class="mx-auto mb-4 h-32 w-32 rounded-full object-cover" loading="lazy">
                    @else
                        <div class="mx-auto mb-4 h-32 w-32 rounded-full bg-brand/10" aria-hidden="true"></div>
                    @endif

                    <h3 class="font-semibold text-brand">{{ $member->name }}</h3>
                    <p class="text-sm text-text/80">{{ $member->role }}</p>

                    @if ($member->bio)
                        <p class="mt-2 text-sm">{{ $member->bio }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
```

- [ ] **Step 7: Policy and resource**

Copy `DocumentPolicy` to `TeamMemberPolicy`, substituting the model and dropping `publish` (team members have no editorial workflow). Then:

```powershell
php artisan make:filament-resource TeamMember --generate --record-title-attribute=name -n
```

In the generated form, add a `SpatieMediaLibraryFileUpload::make('photo')->collection('photo')->image()->maxSize(5120)`, a required `alt` custom-property field with `->afterStateHydrated(fn ($component, $record) => $component->state($record?->photoAlt()))`, and make the table reorderable with `->reorderable('sort_order')->defaultSort('sort_order')`.

Add the `team_grid` block to `PageForm`'s Builder with a single `TextInput::make('heading')`.

- [ ] **Step 8: Run the tests and commit**

```powershell
php artisan migrate
./vendor/bin/pest
git add database/migrations app/Models/TeamMember.php database/factories app/Policies/TeamMemberPolicy.php app/Filament/Resources/TeamMembers app/Enums/BlockType.php resources/views/components/blocks/team-grid.blade.php app/Filament/Resources/Pages tests/Feature/TeamMemberTest.php
git commit -m "feat: team members with photos and a team grid block

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 9: Programme detail pages

**Files:**
- Create: `app/Http/Controllers/ProgrammeController.php`, `resources/views/programmes/show.blade.php`
- Modify: `routes/web.php`, `resources/views/components/content/programme-card.blade.php`
- Test: `tests/Feature/ProgrammeRoutingTest.php`

**Interfaces:**
- Consumes: `Programme::published()`
- Produces: `route('programmes.show', $programme)` — replacing Spec 1's `TODO(spec-3)` placeholder links

- [ ] **Step 1: Write the failing tests**

`tests/Feature/ProgrammeRoutingTest.php`:

```php
<?php

use App\Enums\ContentStatus;
use App\Models\Programme;

it('serves a published programme', function () {
    $programme = Programme::factory()->create([
        'title' => 'Marine Conservation Programme',
        'slug' => 'marine-conservation',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $this->withoutVite()->get("/environment-programmes/{$programme->slug}")
        ->assertOk()
        ->assertSee('Marine Conservation Programme');
});

it('404s an unpublished programme', function () {
    $programme = Programme::factory()->create(['slug' => 'draft-programme', 'status' => ContentStatus::Draft]);

    $this->withoutVite()->get("/environment-programmes/{$programme->slug}")->assertNotFound();
});

it('links programme cards to their detail page', function () {
    $programme = Programme::factory()->create([
        'title' => 'Waste Programme',
        'slug' => 'waste-programme',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
        'is_featured' => true,
    ]);

    $this->withoutVite()->get('/')->assertSee("/environment-programmes/{$programme->slug}", false);
});
```

- [ ] **Step 2: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="ProgrammeRouting"
```

Expected: FAIL — 404, and the homepage still renders `#`.

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/ProgrammeController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use Illuminate\Contracts\View\View;

class ProgrammeController extends Controller
{
    public function show(string $slug): View
    {
        $programme = Programme::published()->where('slug', $slug)->firstOrFail();

        return view('programmes.show', [
            'programme' => $programme,
            'projects' => $programme->projects()->published()->latest('published_at')->take(6)->get(),
        ]);
    }
}
```

- [ ] **Step 4: Register the route BEFORE the catch-all**

In `routes/web.php`, above the `pages.show` catch-all:

```php
Route::get('/environment-programmes/{slug}', [\App\Http\Controllers\ProgrammeController::class, 'show'])
    ->name('programmes.show');
```

- [ ] **Step 5: Build the view**

`resources/views/programmes/show.blade.php` — mirror `pages/show.blade.php`'s structure: `<x-layouts.public>` with the programme's SEO title and description, an `<h1>` for the title, the intro from `summary`, the featured image with its stored alt text, the `body` rendered as sanitised HTML, and a related-projects section that hides entirely when `$projects` is empty.

- [ ] **Step 6: Update the programme card link**

In `resources/views/components/content/programme-card.blade.php`, replace the `#` href and its `TODO(spec-3)` comment with:

```blade
href="{{ route('programmes.show', $programme->slug) }}"
```

- [ ] **Step 7: Run the tests and commit**

```powershell
./vendor/bin/pest
git add app/Http/Controllers/ProgrammeController.php resources/views/programmes routes/web.php resources/views/components/content/programme-card.blade.php tests/Feature/ProgrammeRoutingTest.php
git commit -m "feat: programme detail pages, wiring up homepage cards

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 10: Database-driven navigation

**Files:**
- Create: migration, `app/Models/NavigationItem.php`, `app/Policies/NavigationItemPolicy.php`, `app/Filament/Resources/NavigationItems/**`, `database/seeders/NavigationItemSeeder.php`
- Modify: `resources/views/components/site/header.blade.php`, `resources/views/components/site/footer.blade.php`, `database/seeders/DatabaseSeeder.php`
- Delete: `config/navigation.php`
- Test: `tests/Feature/NavigationTest.php`

**Interfaces:**
- Consumes: nothing
- Produces: `NavigationItem::active()`

- [ ] **Step 1: Create the migration**

```php
Schema::create('navigation_items', function (Blueprint $table) {
    $table->id();
    $table->string('label');
    $table->string('url');
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

No `is_demo` — like `QuickLink`, these are real content and must survive `demo:purge`.

- [ ] **Step 2: Write the failing tests**

`tests/Feature/NavigationTest.php`:

```php
<?php

use App\Models\NavigationItem;

it('returns active items in sort order', function () {
    NavigationItem::create(['label' => 'Second', 'url' => '/b', 'sort_order' => 2]);
    NavigationItem::create(['label' => 'First', 'url' => '/a', 'sort_order' => 1]);
    NavigationItem::create(['label' => 'Hidden', 'url' => '/c', 'sort_order' => 0, 'is_active' => false]);

    expect(NavigationItem::active()->pluck('label')->all())->toBe(['First', 'Second']);
});

it('renders the header from the database', function () {
    NavigationItem::create(['label' => 'Waste & Recycling', 'url' => '/waste-and-recycling', 'sort_order' => 1]);

    $this->withoutVite()->get('/')->assertSee('Waste &amp; Recycling', false);
});

it('omits an inactive item from the header', function () {
    NavigationItem::create(['label' => 'Retired Section', 'url' => '/retired', 'sort_order' => 1, 'is_active' => false]);

    $this->withoutVite()->get('/')->assertDontSee('Retired Section');
});

it('seeds the twelve navigation items', function () {
    $this->seed(\Database\Seeders\NavigationItemSeeder::class);

    expect(NavigationItem::active()->count())->toBe(12);
});
```

- [ ] **Step 3: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="Navigation"
```

Expected: FAIL — model not found.

- [ ] **Step 4: Create the model**

`app/Models/NavigationItem.php` — identical in shape to `app/Models/QuickLink.php`, substituting the class name and dropping `description` and `icon`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    protected $fillable = ['label', 'url', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
```

- [ ] **Step 5: Create the seeder**

`database/seeders/NavigationItemSeeder.php` — the same twelve items currently in `config/navigation.php`, keyed on `label` (not `url`, for the reason recorded in `QuickLinkSeeder`: the URLs change as specs land, the labels do not):

```php
<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use Illuminate\Database\Seeder;

class NavigationItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About Us', 'url' => '/about'],
            ['label' => 'Environment Programmes', 'url' => '/environment-programmes'],
            ['label' => 'Waste & Recycling', 'url' => '/waste-and-recycling'],
            ['label' => 'Biodiversity & Conservation', 'url' => '/biodiversity-and-conservation'],
            ['label' => 'Climate & Marine', 'url' => '/climate-and-marine'],
            ['label' => 'Projects', 'url' => '/projects'],
            ['label' => 'News & Events', 'url' => '/news'],
            ['label' => 'Resources', 'url' => '/resources'],
            ['label' => 'Gallery', 'url' => '/gallery'],
            ['label' => 'Vacancies', 'url' => '/vacancies'],
            ['label' => 'Contact Us', 'url' => '/contact'],
        ];

        foreach ($items as $index => $item) {
            NavigationItem::updateOrCreate(
                ['label' => $item['label']],
                [...$item, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
```

Register it in `DatabaseSeeder::run()` alongside `QuickLinkSeeder::class`.

- [ ] **Step 6: Switch the header to the database**

In `resources/views/components/site/header.blade.php`, replace both occurrences of `config('navigation.primary')` with `\App\Models\NavigationItem::active()->get()`, and change `$item['label']` / `$item['url']` to `$item->label` / `$item->url`.

Better: resolve it once in the existing `View::composer` in `app/Providers/AppServiceProvider.php` that already shares `$settings` with `components.site.*`, and share `$navigation` the same way — one query for both nav lists rather than two.

- [ ] **Step 7: Delete the config file**

```powershell
git rm config/navigation.php
```

Grep for any remaining reference: `grep -rn "navigation.primary" app resources config` must return nothing.

- [ ] **Step 8: Policy and resource**

Copy `QuickLinkPolicy` to `NavigationItemPolicy`. Then:

```powershell
php artisan make:filament-resource NavigationItem --generate --record-title-attribute=label -n
```

Apply the same URL regex used by `QuickLinkForm` — `->regex('/^(\/(?!\/)\S*|https?:\/\/\S+)$/')` — and make the table `->reorderable('sort_order')->defaultSort('sort_order')`.

- [ ] **Step 9: Run the tests and commit**

```powershell
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\NavigationItemSeeder
./vendor/bin/pest
git add database/migrations app/Models/NavigationItem.php app/Policies/NavigationItemPolicy.php app/Filament/Resources/NavigationItems database/seeders resources/views/components/site app/Providers/AppServiceProvider.php tests/Feature/NavigationTest.php
git rm config/navigation.php
git commit -m "feat: database-driven navigation replacing config/navigation.php

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 11: Demo content for pages and team

**Files:**
- Create: `database/seeders/PageSeeder.php`, `database/seeders/TeamMemberSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`, `app/Console/Commands/PurgeDemoContent.php`
- Test: `tests/Feature/DemoContentTest.php`

**Interfaces:**
- Consumes: `Page`, `TeamMember`
- Produces: demo pages covering the full IA

- [ ] **Step 1: Extend the purge command**

Add `Page::class` and `TeamMember::class` to `PurgeDemoContent::MODELS`. **`Page` must come before its own parents are deleted** — since `parent_id` uses `nullOnDelete()`, order within pages does not matter, but children are safest first. Put `TeamMember::class` and `Page::class` at the front of the array.

- [ ] **Step 2: Write the failing test**

Append to `tests/Feature/DemoContentTest.php`:

```php
it('purges demo pages and team members but keeps navigation items', function () {
    $this->seed(\Database\Seeders\NavigationItemSeeder::class);
    $this->seed(\Database\Seeders\PageSeeder::class);
    $this->seed(\Database\Seeders\TeamMemberSeeder::class);

    expect(\App\Models\Page::count())->toBeGreaterThan(0)
        ->and(\App\Models\TeamMember::count())->toBeGreaterThan(0);

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    expect(\App\Models\Page::count())->toBe(0)
        ->and(\App\Models\TeamMember::count())->toBe(0)
        ->and(\App\Models\NavigationItem::count())->toBe(12);
});
```

- [ ] **Step 3: Run to verify it fails**

```powershell
./vendor/bin/pest --filter="purges demo pages"
```

Expected: FAIL — seeders do not exist.

- [ ] **Step 4: Write the page seeder**

`database/seeders/PageSeeder.php` creates, all with `is_demo => true`, `status => ContentStatus::Published` and `published_at => now()->subWeek()`:

- **About Us** (`about`, no parent), with children **About the Department** (`about-the-department`), **Mandate** (`mandate`), **Mission & Vision** (`mission-and-vision`), **Our Team** (`our-team`, containing a `team_grid` block)
- **Environment Programmes** (`environment-programmes`) — rich text intro plus a `programmes_list` block
- **Waste & Recycling** (`waste-and-recycling`) — rich text, a `card_grid` of services, a `documents_list` filtered to Forms
- **Biodiversity & Conservation** (`biodiversity-and-conservation`) — rich text and an image
- **Climate & Marine** (`climate-and-marine`) — rich text and a callout
- **Contact Us** (`contact`) — rich text and a `contact_details` block
- **Privacy** (`privacy`), **Terms** (`terms`), **Accessibility** (`accessibility`) — rich text, each with `show_in_section_nav => false`

Copy must not name real Niue institutions, statistics, dates or events. Generic environmental language only.

- [ ] **Step 5: Write the team seeder**

`database/seeders/TeamMemberSeeder.php` creates four members with **unmistakably fictional** names and generic roles (Director, Senior Environment Officer, Waste Management Officer, Communications Officer), each `is_demo => true`, with a generated placeholder photo attached via the existing `DemoImageGenerator` and an `alt` custom property set.

Register both seeders in `DatabaseSeeder::run()` after `DemoContentSeeder::class`.

- [ ] **Step 6: Run the tests, seed, and look**

```powershell
php artisan migrate:fresh --seed
./vendor/bin/pest
npm run build
```

Then open `http://niue-doe-website.test` and click every navigation item. **Every one must resolve** — that is this spec's headline success criterion. Check `/about` lists its children, `/about/mandate` renders, `/environment-programmes` lists programmes, and a programme card leads to a detail page.

- [ ] **Step 7: Commit**

```powershell
git add database/seeders app/Console/Commands/PurgeDemoContent.php tests/Feature/DemoContentTest.php
git commit -m "feat: demo pages and team members covering the full IA

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Self-Review

**Spec coverage.** Every section of the spec maps to a task:

| Spec section | Task |
|---|---|
| 4.1 `Page` model, hierarchy, `path` | 1 |
| 4.2 The block set | 3, 4, 5, 8 |
| 4.3 `TeamMember` | 8 |
| 4.4 `NavigationItem` | 10 |
| 5 Routing, reserved slugs, catch-all order | 6 (validation), 7 (routes), 9 (explicit-before-catch-all) |
| 6 Block rendering, unknown blocks, heading hierarchy | 3 |
| 6 Rich text sanitisation on save | 2, 6 (proving it fires through the form) |
| 7 Navigation and site chrome | 10 |
| 8 Testing | every task |
| 9 Demo content | 11 |
| 10 Success criteria | 11 Step 6 |

**Two gaps found and fixed inline while reviewing:**

1. The spec specified seven blocks, but Our Team needs to render the `TeamMember` model — a manual card grid cannot. Task 8 adds a `team_grid` block, making eight. This mirrors the six-to-seven correction made during the spec's own design and is recorded here rather than left implicit.
2. The spec's §7 says the footer's Privacy/Terms/Accessibility column can finally render. Task 11 creates those Pages, but no task updates the footer to link them. **Fold this into Task 10 Step 6**: when switching the header to the database, also add the Privacy/Terms/Accessibility column to `footer.blade.php`, reading `Page::published()->whereIn('slug', ['privacy', 'terms', 'accessibility'])`.

**Type consistency.** `published()`, `active()`, `sectionNav()`, `computePath()`, `photoUrl()`, `photoAlt()`, `sanitise()`, `RESERVED_SLUGS` and `BlockType::view()` are each defined once and used with the same signature throughout. Block `data` key names in Tasks 4 and 5 match the Filament Builder schemas in Task 6.

**One risk flagged, not fixable in the plan.** The catch-all route regex `^(?!admin|storage|livewire)[a-z0-9\-]+(\/[a-z0-9\-]+)?$` permits exactly two path segments. A third level of hierarchy would 404 silently. The spec limits pages to one level of nesting, so this is correct today — but if Spec 3 or later introduces deeper paths, this regex is the thing to change, and its failure mode is a page that exists in the CMS and 404s on the site with no error anywhere.
