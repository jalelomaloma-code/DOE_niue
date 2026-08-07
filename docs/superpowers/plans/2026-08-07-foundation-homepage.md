# Niue DoE Foundation & Homepage — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up the Niue Department of Environment website on Laravel 13 + Filament 5 with a fully database-driven homepage, running locally on Herd and PostgreSQL.

**Architecture:** A plain Laravel 13 skeleton with no starter kit. The public site is server-rendered Blade with zero JavaScript frameworks; the CMS is a Filament 5 panel at `/admin` (which brings Livewire 4, confined to that panel). Content models share three traits — `HasStatus`, `HasBlame`, `HasSeo` — so editorial workflow, authorship and SEO behave identically everywhere. The homepage skeleton is fixed in Blade while every word, image and featured item inside it comes from the database.

**Tech Stack:** PHP 8.4 (Herd), Laravel 13.24, Filament 5.7, Livewire 4, PostgreSQL 18, Tailwind CSS 4, Vite, Pest 5, `spatie/laravel-permission` 8, `spatie/laravel-medialibrary` 11.

**Spec:** `docs/superpowers/specs/2026-08-07-foundation-homepage-design.md` (commit `2cca14a`)

---

## Global Constraints

Every task's requirements implicitly include this section.

- **PHP 8.4 minimum.** Laravel 13.24 requires `^8.3`, but Pest 5.0.4 requires `^8.4`. 8.4 is the floor.
- **Verified version floors:** `laravel/framework` ^13.24, `filament/filament` ^5.7, `spatie/laravel-permission` ^8.3 (`illuminate/contracts: ^12.0|^13.0`), `spatie/laravel-medialibrary` ^11.23, `pestphp/pest` ^5.0.
- **No SPA framework.** React, Vue, Inertia, Svelte and Alpine are forbidden on the public site. Livewire is permitted only inside the Filament panel. The mobile menu is vanilla JavaScript.
- **No starter kit.** The public site has no user accounts and no login. `/admin` is the only authenticated surface.
- **No deployment of any kind.** No DigitalOcean resources, no DNS, no Cloudflare, no production database, no production credentials, no external service configuration. Local only.
- **Palette, exact values:** brand `#003A70`, accent `#FCD116`, eco `#287A4B`, ink `#142B3A`, text `#253238`, surface `#F5F7F6`, white `#FFFFFF`.
- **The yellow rule:** `#FCD116` scores ~1.4:1 on white and must never be text on white, nor a button background with white text. It is only ever a background carrying `#142B3A` text, an active-nav indicator, a thin accent rule, or a small icon fill.
- **Focus rings:** blue ring with white offset on light surfaces; yellow ring on dark navy surfaces.
- **Naming rule:** editorial workflow is always `status`; the project lifecycle is always `project_status`. Never reuse `status` for a lifecycle.
- **Every public query goes through the `published()` scope.** Never hand-write `where('status', ...)` in a controller or view.
- **Alt text is required at upload time** on every Filament image field.
- **No content of any kind is copied from the Fiji Environment website.**
- **Filament 5.7 API authority:** always generate resources with `php artisan make:filament-resource` first. If a generated method signature differs from a snippet in this plan, **the generator is authoritative** — adapt the snippet's body into the generated signature rather than overwriting it.
- **Commit after every task.** Conventional Commits style, ending with `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

---

## File Structure

```
app/
├── Console/Commands/PurgeDemoContent.php     — `demo:purge`
├── Enums/
│   ├── ContentStatus.php                     — Draft/UnderReview/Published/Archived
│   ├── ProjectStatus.php                     — Planned/Active/Completed/OnHold
│   └── UserRole.php                          — role name constants
├── Filament/
│   ├── Pages/
│   │   ├── ManageSiteSettings.php
│   │   └── ManageHomepage.php
│   └── Resources/                            — one directory per content model
├── Http/Controllers/HomeController.php       — the only public controller in Spec 1
├── Models/
│   ├── Concerns/{HasStatus,HasBlame,HasSeo}.php
│   ├── {Programme,Project,NewsArticle,Document}.php
│   ├── {NewsCategory,DocumentCategory,QuickLink}.php
│   ├── {SiteSetting,HomepageSetting}.php
│   └── User.php
├── Policies/                                 — one per content model
└── Providers/Filament/AdminPanelProvider.php

resources/
├── css/app.css                               — Tailwind 4 @theme tokens
├── js/app.js                                 — mobile menu only
└── views/
    ├── components/
    │   ├── layouts/public.blade.php
    │   ├── site/{header,footer}.blade.php
    │   ├── ui/{button,card,section}.blade.php
    │   └── content/{news-card,programme-card,document-row}.blade.php
    ├── errors/{403,404,500}.blade.php
    └── home.blade.php

database/
├── migrations/
└── seeders/{DatabaseSeeder,RoleSeeder,DemoContentSeeder}.php

config/navigation.php                         — fixed IA for Spec 1
tests/Feature/                                — Pest feature tests
docs/                                         — architecture, cms-guide, etc.
```

Rationale for the boundaries: each content model owns its migration, model, policy and Filament resource, and those four files change together whenever that content type changes. Blade components split by responsibility (site chrome / generic UI / content rendering) rather than by page, so the homepage is a thin composition of already-tested pieces.

---

## Task 1: Development environment

**Files:**
- Create: `.env` (git-ignored), `.gitignore`

**Interfaces:**
- Consumes: nothing
- Produces: a working `php` 8.4 CLI, `composer`, a PostgreSQL 18 server, and an empty `niue_doe` database reachable with credentials recorded in `.env`

> **⚠ STOP — HUMAN ACTION REQUIRED IN THIS TASK.** Both installers below open GUI windows and PostgreSQL prompts for a superuser password that cannot be supplied non-interactively. An agent must pause and ask the human to complete these steps, then continue at Step 4.

- [ ] **Step 1: Install Laravel Herd**

```powershell
winget install --id BeyondCode.Herd -e --accept-package-agreements --accept-source-agreements
```

Then **launch Herd once from the Start Menu** so it installs its PHP binaries and registers the `herd` CLI on PATH. Close and reopen the terminal afterwards.

- [ ] **Step 2: Install PostgreSQL 18**

```powershell
winget install --id PostgreSQL.PostgreSQL.18 -e --accept-package-agreements --accept-source-agreements
```

In the installer: accept the default port **5432**, set a superuser password, and **record that password** — it goes into `.env` in Step 5. Do not use a password you use anywhere else.

- [ ] **Step 3: Select PHP 8.4 in Herd**

```powershell
herd php:list
herd use 8.4
```

If 8.4 is not listed, install it from the Herd UI (Settings → PHP) and re-run `herd use 8.4`.

- [ ] **Step 4: Verify the toolchain**

```powershell
php -v          # must report 8.4.x
composer --version
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" --version
node -v         # already present: v24.18.0
```

Expected: PHP reports 8.4 or higher. **If PHP reports 8.3, stop** — Pest 5 will not install.

- [ ] **Step 5: Create the database**

```powershell
$env:PGPASSWORD = "<the superuser password from Step 2>"
& "C:\Program Files\PostgreSQL\18\bin\createdb.exe" -U postgres -h 127.0.0.1 niue_doe
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -h 127.0.0.1 -l
```

Expected: `niue_doe` appears in the database list.

- [ ] **Step 6: Report the environment state**

No commit in this task — the repository has no application files yet. Report the verified PHP, Composer, PostgreSQL and Node versions before proceeding to Task 2.

---

## Task 2: Laravel 13 skeleton on PostgreSQL

**Files:**
- Create: the full `laravel/laravel` v13 skeleton in the project root
- Modify: `.env`, `.env.example`
- Test: `tests/Feature/SmokeTest.php`

**Interfaces:**
- Consumes: PHP 8.4, Composer, the `niue_doe` database from Task 1
- Produces: a booting Laravel application on PostgreSQL, with Pest available as `./vendor/bin/pest`

- [ ] **Step 1: Create the skeleton into the existing repository**

The repository already contains `docs/`, so `create-project` cannot target the directory directly. Build into a temporary directory and merge:

```powershell
Set-Location "C:\Users\jalel\Documents"
composer create-project laravel/laravel:^13.0 niue-doe-tmp --no-interaction
Copy-Item -Path "niue-doe-tmp\*" -Destination "niue-doe-website\" -Recurse -Force
Copy-Item -Path "niue-doe-tmp\.env.example",  "niue-doe-tmp\.gitignore", "niue-doe-tmp\.editorconfig" -Destination "niue-doe-website\" -Force
Remove-Item "niue-doe-tmp" -Recurse -Force
Set-Location "niue-doe-website"
```

- [ ] **Step 2: Verify the framework version**

```powershell
php artisan --version
```

Expected: `Laravel Framework 13.x`. If it reports 12.x, the constraint did not resolve — stop and investigate rather than continuing.

- [ ] **Step 3: Point the application at PostgreSQL**

Edit `.env`:

```dotenv
APP_NAME="Niue Department of Environment"
APP_URL=https://niue-doe-website.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=niue_doe
DB_USERNAME=postgres
DB_PASSWORD=<the superuser password from Task 1>
```

Apply the same keys to `.env.example` with an empty `DB_PASSWORD`. `.env.example` is the handover contract and must stay current for the life of the project.

- [ ] **Step 4: Run the framework migrations**

```powershell
php artisan migrate
```

Expected: the `users`, `cache`, `jobs` and `sessions` tables are created without error. A connection failure here means `.env` credentials are wrong — fix before continuing.

- [ ] **Step 5: Install Pest 5**

```powershell
composer require pestphp/pest:^5.0 pestphp/pest-plugin-laravel --dev --with-all-dependencies --no-interaction
./vendor/bin/pest --init
```

- [ ] **Step 6: Write the smoke test**

Create `tests/Feature/SmokeTest.php`:

```php
<?php

it('boots the application and reaches the database', function () {
    expect(\Illuminate\Support\Facades\DB::connection()->getDriverName())->toBe('pgsql');
    expect(\Illuminate\Support\Facades\DB::connection()->getPdo())->not->toBeNull();
});
```

- [ ] **Step 7: Run the test**

```powershell
./vendor/bin/pest --filter="boots the application"
```

Expected: PASS.

- [ ] **Step 8: Serve the site through Herd**

```powershell
herd link niue-doe-website
herd secure niue-doe-website
```

Open `https://niue-doe-website.test` — the default Laravel welcome page must render over HTTPS.

- [ ] **Step 9: Commit**

```powershell
git add -A
git commit -m "feat: Laravel 13 skeleton on PostgreSQL with Pest

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 3: Design tokens and the base layout shell

**Files:**
- Modify: `resources/css/app.css`, `package.json`, `vite.config.js`
- Create: `resources/views/components/layouts/public.blade.php`
- Test: `tests/Feature/LayoutTest.php`

**Interfaces:**
- Consumes: the Laravel skeleton from Task 2
- Produces: `<x-layouts.public>` accepting `$title` and `$description` props; Tailwind tokens named `brand`, `accent`, `eco`, `ink`, `text`, `surface`

- [ ] **Step 1: Install Tailwind 4 and Public Sans**

```powershell
npm install
npm install -D tailwindcss @tailwindcss/vite @fontsource/public-sans
```

- [ ] **Step 2: Register the Tailwind Vite plugin**

`vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
        tailwindcss(),
    ],
});
```

- [ ] **Step 3: Define the design tokens**

Replace `resources/css/app.css`:

```css
@import "tailwindcss";
@import "@fontsource/public-sans/400.css";
@import "@fontsource/public-sans/600.css";
@import "@fontsource/public-sans/700.css";

@theme {
    --color-brand: #003A70;
    --color-accent: #FCD116;
    --color-eco: #287A4B;
    --color-ink: #142B3A;
    --color-text: #253238;
    --color-surface: #F5F7F6;

    --font-sans: "Public Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

/* Focus rings switch by surface. Blue on light, yellow on dark navy.
   Yellow on white measures ~1.4:1 and is never used as a focus ring there. */
:focus-visible {
    outline: 2px solid var(--color-brand);
    outline-offset: 2px;
}

.on-dark :focus-visible {
    outline-color: var(--color-accent);
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
```

- [ ] **Step 4: Write the failing layout test**

Create `tests/Feature/LayoutTest.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/__layout-probe', fn () => view('probe'));
});

it('renders the document shell with a skip link and dynamic title', function () {
    \Illuminate\Support\Facades\View::addNamespace('probe', resource_path('views'));

    $response = $this->withoutVite()->get('/__layout-probe');

    $response->assertOk();
    $response->assertSee('Skip to main content');
    $response->assertSee('<html lang="en"', false);
    $response->assertSee('<main', false);
});
```

Create the probe view `resources/views/probe.blade.php`:

```blade
<x-layouts.public title="Probe" description="Probe description">
    <h1>Probe</h1>
</x-layouts.public>
```

- [ ] **Step 5: Run the test to verify it fails**

```powershell
./vendor/bin/pest --filter="renders the document shell"
```

Expected: FAIL — `Unable to locate a class or view for component [layouts.public]`.

- [ ] **Step 6: Build the layout**

Create `resources/views/components/layouts/public.blade.php`:

```blade
@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title . ' — ' : '' }}Niue Department of Environment</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface font-sans text-text antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50
              focus:rounded focus:bg-brand focus:px-4 focus:py-2 focus:text-white">
        Skip to main content
    </a>

    <x-site.header />

    <main id="main" tabindex="-1">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
```

Header and footer are built in Task 15. Until then, create minimal placeholder components so the layout renders:

`resources/views/components/site/header.blade.php` → `<header class="bg-brand text-white p-4">Niue Department of Environment</header>`
`resources/views/components/site/footer.blade.php` → `<footer class="bg-ink text-white p-4">Government of Niue</footer>`

- [ ] **Step 7: Run the test to verify it passes**

```powershell
./vendor/bin/pest --filter="renders the document shell"
npm run build
```

Expected: test PASSES and the Vite build completes without error.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: Tailwind 4 design tokens and public layout shell

Palette declared as semantic tokens. Focus rings switch by surface
because Niue Yellow measures ~1.4:1 on white.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 4: Filament 5 admin panel

**Files:**
- Create: `app/Providers/Filament/AdminPanelProvider.php` (generated)
- Modify: `bootstrap/providers.php` (generated), `app/Models/User.php`
- Test: `tests/Feature/AdminPanelTest.php`

**Interfaces:**
- Consumes: the Laravel skeleton and PostgreSQL connection
- Produces: a Filament panel at `/admin` with brand colours; `User::canAccessPanel(Panel $panel): bool`

- [ ] **Step 1: Install Filament 5**

```powershell
composer require filament/filament:"^5.7" --no-interaction
php artisan filament:install --panels
```

When prompted for the panel ID, enter `admin`.

- [ ] **Step 2: Write the failing access test**

Create `tests/Feature/AdminPanelTest.php`:

```php
<?php

use App\Models\User;

it('redirects anonymous visitors away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});

it('lets an authenticated user reach the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});
```

- [ ] **Step 3: Run the test**

```powershell
./vendor/bin/pest --filter="admin panel"
```

Expected: the anonymous test PASSES; the authenticated test may fail until `canAccessPanel` is defined.

- [ ] **Step 4: Implement panel access on the User model**

Add to `app/Models/User.php`:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    // ... existing body ...

    public function canAccessPanel(Panel $panel): bool
    {
        // Role checks are added in Task 5. Until then any authenticated
        // user may reach the panel; Task 5 tightens this.
        return true;
    }
}
```

- [ ] **Step 5: Apply brand colours to the panel**

In `app/Providers/Filament/AdminPanelProvider.php`, set the panel colours. Adapt to the generated signature:

```php
use Filament\Support\Colors\Color;

->colors([
    'primary' => Color::hex('#003A70'),
    'success' => Color::hex('#287A4B'),
    'warning' => Color::hex('#FCD116'),
])
->brandName('Niue DoE')
```

- [ ] **Step 6: Create the first Super Admin**

```powershell
php artisan make:filament-user
```

Use a real name and email; record the password locally. This account is a development account only and must never be reused in production.

- [ ] **Step 7: Run the tests and confirm the panel loads**

```powershell
./vendor/bin/pest --filter="admin panel"
```

Expected: PASS. Then open `https://niue-doe-website.test/admin` and log in.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: Filament 5 admin panel with brand colours

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 5: Roles and panel access control

**Files:**
- Create: `app/Enums/UserRole.php`, `database/seeders/RoleSeeder.php`
- Modify: `app/Models/User.php`, `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/RoleAccessTest.php`

**Interfaces:**
- Consumes: the Filament panel from Task 4
- Produces: `UserRole` enum with cases `SuperAdmin`, `WebsiteManager`, `Editor`, `Viewer` each exposing `->value` as the Spatie role name; `User::canAccessPanel()` gated on role

- [ ] **Step 1: Install spatie/laravel-permission**

```powershell
composer require spatie/laravel-permission:"^8.3" --no-interaction
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

- [ ] **Step 2: Define the role enum**

Create `app/Enums/UserRole.php`:

```php
<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case WebsiteManager = 'website_manager';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::WebsiteManager => 'Website Manager',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
        };
    }

    /** Roles permitted to publish content. Editors submit for review instead. */
    public function canPublish(): bool
    {
        return in_array($this, [self::SuperAdmin, self::WebsiteManager], true);
    }
}
```

- [ ] **Step 3: Write the failing role test**

Create `tests/Feature/RoleAccessTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('lets a super admin reach the panel', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

it('lets a viewer reach the panel', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::Viewer->value);

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

it('refuses a user with no role at all', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('knows which roles may publish', function () {
    expect(UserRole::SuperAdmin->canPublish())->toBeTrue()
        ->and(UserRole::WebsiteManager->canPublish())->toBeTrue()
        ->and(UserRole::Editor->canPublish())->toBeFalse()
        ->and(UserRole::Viewer->canPublish())->toBeFalse();
});
```

- [ ] **Step 4: Run the test to verify it fails**

```powershell
./vendor/bin/pest --filter="RoleAccess"
```

Expected: FAIL — `refuses a user with no role at all` returns 200 because Task 4 returns `true` unconditionally.

- [ ] **Step 5: Apply the role trait and tighten panel access**

In `app/Models/User.php`:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function mayPublish(): bool
    {
        foreach (UserRole::cases() as $role) {
            if ($this->hasRole($role->value) && $role->canPublish()) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 6: Seed the roles**

Create `database/seeders/RoleSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value);
        }
    }
}
```

Call it from `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([RoleSeeder::class]);
}
```

- [ ] **Step 7: Run the tests and seed**

```powershell
./vendor/bin/pest --filter="RoleAccess"
php artisan db:seed
```

Expected: all four tests PASS.

- [ ] **Step 8: Assign your development account the Super Admin role**

```powershell
php artisan tinker --execute="App\Models\User::first()->assignRole('super_admin');"
```

- [ ] **Step 9: Commit**

```powershell
git add -A
git commit -m "feat: roles and Filament panel access control

Four roles per spec 4.3. Editors cannot publish; they move content to
Under Review for a Website Manager to publish.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 6: Cross-cutting content traits

**Files:**
- Create: `app/Enums/ContentStatus.php`, `app/Enums/ProjectStatus.php`, `app/Models/Concerns/HasStatus.php`, `app/Models/Concerns/HasBlame.php`, `app/Models/Concerns/HasSeo.php`
- Test: `tests/Feature/ContentStatusTest.php`

**Interfaces:**
- Consumes: nothing beyond the framework
- Produces:
  - `ContentStatus` enum: `Draft`, `UnderReview`, `Published`, `Archived`, each with `->label(): string` and `->color(): string`
  - `HasStatus` trait: `scopePublished(Builder $q): Builder`, `isPublished(): bool`
  - `HasBlame` trait: `creator(): BelongsTo`, `editor(): BelongsTo`, auto-fills `created_by`/`updated_by`
  - `HasSeo` trait: `seoTitle(): string`, `seoDescription(): ?string`; consuming models **must** implement `seoFallbackDescription(): ?string`

This is the single most correctness-critical task in the plan. The `published()` scope is the only thing standing between an editor's draft and the public internet.

- [ ] **Step 1: Write the failing tests first**

Create `tests/Feature/ContentStatusTest.php`. The test uses `Programme`, created in Task 8 — so **this task's tests are written now and run green at the end of Task 8.** Write them here so the scope is designed against its tests:

```php
<?php

use App\Enums\ContentStatus;
use App\Models\Programme;

it('includes only published records with a past publish date', function () {
    Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Visible programme',
    ]);

    foreach ([ContentStatus::Draft, ContentStatus::UnderReview, ContentStatus::Archived] as $hidden) {
        Programme::factory()->create([
            'status' => $hidden,
            'published_at' => now()->subDay(),
        ]);
    }

    Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->addWeek(),
        'title' => 'Scheduled programme',
    ]);

    Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => null,
    ]);

    $visible = Programme::published()->get();

    expect($visible)->toHaveCount(1)
        ->and($visible->first()->title)->toBe('Visible programme');
});

it('never leaks a future-dated article', function () {
    Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->addMinute(),
    ]);

    expect(Programme::published()->count())->toBe(0);
});
```

- [ ] **Step 2: Create the ContentStatus enum**

`app/Enums/ContentStatus.php`:

```php
<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under Review',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::UnderReview => 'warning',
            self::Published => 'success',
            self::Archived => 'danger',
        };
    }

    /** @return array<string, string> value => label, for Filament select fields */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
```

- [ ] **Step 3: Create the ProjectStatus enum**

`app/Enums/ProjectStatus.php`. Note the deliberate separation from `ContentStatus` — this is the real-world lifecycle, never the editorial workflow:

```php
<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::OnHold => 'On Hold',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Active => 'success',
            self::Completed => 'info',
            self::OnHold => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
```

- [ ] **Step 4: Create the HasStatus trait**

`app/Models/Concerns/HasStatus.php`:

```php
<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

trait HasStatus
{
    /**
     * The ONLY sanctioned way to query content for public display.
     * Published, with a publish date that has actually arrived.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', ContentStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
```

- [ ] **Step 5: Create the HasBlame trait**

`app/Models/Concerns/HasBlame.php`:

```php
<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasBlame
{
    public static function bootHasBlame(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by ??= auth()->id();
                $model->updated_by ??= auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
```

- [ ] **Step 6: Create the HasSeo trait**

`app/Models/Concerns/HasSeo.php`:

```php
<?php

namespace App\Models\Concerns;

trait HasSeo
{
    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function seoDescription(): ?string
    {
        return $this->seo_description ?: $this->seoFallbackDescription();
    }

    /**
     * Each model names its own summary column, so it supplies the fallback.
     * Implement on every model that uses this trait.
     */
    abstract public function seoFallbackDescription(): ?string;
}
```

- [ ] **Step 7: Verify the enums in isolation**

The scope tests cannot run until Task 8 creates `Programme`. Confirm the enums load now:

```powershell
php artisan tinker --execute="dump(App\Enums\ContentStatus::options()); dump(App\Enums\ProjectStatus::options());"
```

Expected: both print four labelled options.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: content status enums and cross-cutting model traits

The published() scope is the single sanctioned public query path:
Published status AND a published_at that has already arrived.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 7: Taxonomies — news and document categories

**Files:**
- Create: migrations for `news_categories` and `document_categories`, `app/Models/NewsCategory.php`, `app/Models/DocumentCategory.php`, their factories, and two Filament resources
- Test: `tests/Feature/TaxonomyTest.php`

**Interfaces:**
- Consumes: nothing
- Produces: `NewsCategory` and `DocumentCategory` models, each with `name`, `slug`, `sort_order` and a `->articles()` / `->documents()` relation added in Tasks 10 and 11

- [ ] **Step 1: Create the migrations**

```powershell
php artisan make:migration create_news_categories_table
php artisan make:migration create_document_categories_table
```

Both bodies follow the same shape — `create_news_categories_table`:

```php
Schema::create('news_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});
```

`create_document_categories_table`:

```php
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/TaxonomyTest.php`:

```php
<?php

use App\Models\DocumentCategory;
use App\Models\NewsCategory;

it('stores a news category with a unique slug', function () {
    NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);

    expect(NewsCategory::where('slug', 'announcements')->exists())->toBeTrue();
});

it('stores the six seeded document categories in order', function () {
    $this->seed(\Database\Seeders\DocumentCategorySeeder::class);

    expect(DocumentCategory::orderBy('sort_order')->pluck('name')->all())
        ->toBe(['Publications', 'Policies', 'Legislation', 'Forms', 'Reports', 'Downloads']);
});
```

- [ ] **Step 3: Run the test to verify it fails**

```powershell
./vendor/bin/pest --filter="Taxonomy"
```

Expected: FAIL — `Class "App\Models\NewsCategory" not found`.

- [ ] **Step 4: Create the models**

`app/Models/NewsCategory.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'sort_order'];
}
```

`app/Models/DocumentCategory.php` is identical apart from the class name.

- [ ] **Step 5: Create the document category seeder**

`database/seeders/DocumentCategorySeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocumentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Publications', 'Policies', 'Legislation', 'Forms', 'Reports', 'Downloads'];

        foreach ($categories as $index => $name) {
            DocumentCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index],
            );
        }
    }
}
```

Register it in `DatabaseSeeder::run()` after `RoleSeeder::class`.

- [ ] **Step 6: Run the tests**

```powershell
php artisan migrate
./vendor/bin/pest --filter="Taxonomy"
```

Expected: PASS.

- [ ] **Step 7: Generate the Filament resources**

```powershell
php artisan make:filament-resource NewsCategory --generate
php artisan make:filament-resource DocumentCategory --generate
```

In each generated resource, set the navigation group to `'Content'` and make the table reorderable on `sort_order`.

- [ ] **Step 8: Verify in the panel and commit**

Log in at `/admin`, create a news category, confirm it saves.

```powershell
git add -A
git commit -m "feat: news and document category taxonomies

Document categories are an editable table rather than an enum so the
Department can add one without a developer.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 8: Programmes

**Files:**
- Create: migration `create_programmes_table`, `app/Models/Programme.php`, `database/factories/ProgrammeFactory.php`, `app/Policies/ProgrammePolicy.php`, `app/Filament/Resources/Programmes/*`
- Test: `tests/Feature/ContentStatusTest.php` (written in Task 6, runs green here), `tests/Feature/ProgrammeTest.php`

**Interfaces:**
- Consumes: `HasStatus`, `HasBlame`, `HasSeo` from Task 6
- Produces: `Programme` model with `->projects()` HasMany (consumed by Task 9), a `featured` scope, and a `featured_image` media collection

- [ ] **Step 1: Install medialibrary**

```powershell
composer require spatie/laravel-medialibrary:"^11.23" --no-interaction
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

- [ ] **Step 2: Create the migration**

```powershell
php artisan make:migration create_programmes_table
```

```php
Schema::create('programmes', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('summary')->nullable();
    $table->longText('body')->nullable();
    $table->boolean('is_featured')->default(false);
    $table->unsignedInteger('sort_order')->default(0);
    $table->string('status')->default('draft')->index();
    $table->timestamp('published_at')->nullable()->index();
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->boolean('is_demo')->default(false)->index();
    $table->timestamps();
});
```

- [ ] **Step 3: Create the model**

`app/Models/Programme.php`:

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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Programme extends Model implements HasMedia
{
    use HasBlame, HasFactory, HasSeo, HasStatus, InteractsWithMedia;

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('og_image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(600)->height(400)->nonQueued();
        $this->addMediaConversion('hero')->width(1920)->height(900)->nonQueued();
    }
}
```

- [ ] **Step 4: Create the factory**

`database/factories/ProgrammeFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProgrammeFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(15),
            'body' => fake()->paragraphs(3, true),
            'is_featured' => false,
            'sort_order' => 0,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'is_demo' => true,
        ];
    }
}
```

- [ ] **Step 5: Run the Task 6 scope tests — they must now pass**

```powershell
php artisan migrate
./vendor/bin/pest tests/Feature/ContentStatusTest.php
```

Expected: both tests PASS. **If `never leaks a future-dated article` fails, stop and fix the scope before doing anything else** — that failure means drafts can reach the public.

- [ ] **Step 6: Write and run the programme test**

`tests/Feature/ProgrammeTest.php`:

```php
<?php

use App\Models\Programme;

it('filters featured programmes', function () {
    Programme::factory()->count(3)->create();
    Programme::factory()->create(['is_featured' => true, 'title' => 'Marine Conservation']);

    expect(Programme::published()->featured()->count())->toBe(1);
});

it('falls back to the summary for the SEO description', function () {
    $programme = Programme::factory()->create([
        'summary' => 'Protecting Niue reef systems.',
        'seo_description' => null,
    ]);

    expect($programme->seoDescription())->toBe('Protecting Niue reef systems.');
});
```

```powershell
./vendor/bin/pest --filter="Programme"
```

Expected: PASS.

- [ ] **Step 6b: Create the policy**

`app/Policies/ProgrammePolicy.php` — Editors may create and update but not publish or delete:

```php
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Programme;
use App\Models\User;

class ProgrammePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    public function view(User $user, Programme $programme): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function update(User $user, Programme $programme): bool
    {
        return ! $user->hasRole(UserRole::Viewer->value);
    }

    public function publish(User $user, Programme $programme): bool
    {
        return $user->mayPublish();
    }

    public function delete(User $user, Programme $programme): bool
    {
        return $user->hasAnyRole([UserRole::SuperAdmin->value, UserRole::WebsiteManager->value]);
    }
}
```

- [ ] **Step 7: Generate the Filament resource**

```powershell
php artisan make:filament-resource Programme --generate
```

In the generated form schema, add — adapting to the generated signature:

- `TextInput::make('title')->required()->live(onBlur: true)` populating `slug` via `Str::slug`
- `TextInput::make('slug')->required()->unique(ignoreRecord: true)`
- `Textarea::make('summary')->rows(3)->maxLength(300)`
- `RichEditor::make('body')`
- `SpatieMediaLibraryFileUpload::make('featured_image')->collection('featured_image')->image()`
- **an `alt` custom property on that upload marked `->required()`** — alt text is enforced at upload time per the global constraints
- `Select::make('status')->options(ContentStatus::options())->required()->disabled(fn () => ! auth()->user()->mayPublish() )` for the Published option only
- `DateTimePicker::make('published_at')`
- `Toggle::make('is_featured')`
- an SEO section containing `seo_title` and `seo_description`

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: programmes with media, policy and CMS resource

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 9: Projects

**Files:**
- Create: migration `create_projects_table`, `app/Models/Project.php`, factory, `app/Policies/ProjectPolicy.php`, `app/Filament/Resources/Projects/*`
- Test: `tests/Feature/ProjectTest.php`

**Interfaces:**
- Consumes: `Programme::projects()` from Task 8, `ProjectStatus` from Task 6
- Produces: `Project` model with `->programme()` BelongsTo and a `featured` scope

- [ ] **Step 1: Create the migration**

```powershell
php artisan make:migration create_projects_table
```

```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('summary')->nullable();
    $table->longText('body')->nullable();
    $table->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
    $table->string('project_status')->default('planned')->index();
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    $table->boolean('is_featured')->default(false);
    $table->string('status')->default('draft')->index();
    $table->timestamp('published_at')->nullable()->index();
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->boolean('is_demo')->default(false)->index();
    $table->timestamps();
});
```

Note both state columns: `status` is the editorial workflow, `project_status` is the real-world lifecycle. Never conflate them.

- [ ] **Step 2: Write the failing test**

`tests/Feature/ProjectTest.php`:

```php
<?php

use App\Enums\ContentStatus;
use App\Enums\ProjectStatus;
use App\Models\Programme;
use App\Models\Project;

it('keeps the editorial status and the project lifecycle independent', function () {
    $project = Project::factory()->create([
        'status' => ContentStatus::Draft,
        'project_status' => ProjectStatus::Active,
    ]);

    expect($project->status)->toBe(ContentStatus::Draft)
        ->and($project->project_status)->toBe(ProjectStatus::Active)
        ->and(Project::published()->count())->toBe(0);
});

it('belongs to a programme', function () {
    $programme = Programme::factory()->create(['title' => 'Waste Management']);
    $project = Project::factory()->create(['programme_id' => $programme->id]);

    expect($project->programme->title)->toBe('Waste Management')
        ->and($programme->projects)->toHaveCount(1);
});
```

- [ ] **Step 3: Run to verify it fails**

```powershell
./vendor/bin/pest --filter="Project"
```

Expected: FAIL — `Class "App\Models\Project" not found`.

- [ ] **Step 4: Create the model**

`app/Models/Project.php` — same trait set as `Programme`, plus:

```php
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
```

Media collections and conversions are identical to `Programme` (Task 8, Step 3) — repeat them verbatim.

- [ ] **Step 5: Create the factory**

Mirror `ProgrammeFactory`, adding `'project_status' => ProjectStatus::Active` and `'programme_id' => null`.

- [ ] **Step 6: Run the tests**

```powershell
php artisan migrate
./vendor/bin/pest --filter="Project"
```

Expected: PASS.

- [ ] **Step 7: Policy and Filament resource**

Copy `ProgrammePolicy` to `ProjectPolicy`, substituting the model. Then:

```powershell
php artisan make:filament-resource Project --generate
```

Add to the form: `Select::make('programme_id')->relationship('programme', 'title')->searchable()`, `Select::make('project_status')->options(ProjectStatus::options())->required()`, `DatePicker::make('start_date')`, `DatePicker::make('end_date')->afterOrEqual('start_date')`, plus the same title/slug/summary/body/media/status/SEO fields as Programme.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: projects with programme relationship and lifecycle status

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 10: News articles

**Files:**
- Create: migration `create_news_articles_table`, `app/Models/NewsArticle.php`, factory, `app/Policies/NewsArticlePolicy.php`, `app/Filament/Resources/NewsArticles/*`
- Test: `tests/Feature/NewsArticleTest.php`

**Interfaces:**
- Consumes: `NewsCategory` from Task 7
- Produces: `NewsArticle` with `->category()` BelongsTo, `->byline(): string`, and the three Facebook columns for Spec 5

- [ ] **Step 1: Create the migration**

```php
Schema::create('news_articles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt')->nullable();
    $table->longText('body')->nullable();
    $table->foreignId('news_category_id')->nullable()->constrained('news_categories')->nullOnDelete();
    $table->string('author_name')->nullable();
    $table->boolean('is_featured')->default(false);
    $table->string('status')->default('draft')->index();
    $table->timestamp('published_at')->nullable()->index();
    // Facebook integration is Spec 5; the columns land now to avoid migration churn.
    $table->boolean('share_to_facebook')->default(false);
    $table->timestamp('facebook_posted_at')->nullable();
    $table->string('facebook_post_id')->nullable();
    $table->string('seo_title')->nullable();
    $table->text('seo_description')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->boolean('is_demo')->default(false)->index();
    $table->timestamps();
});
```

- [ ] **Step 2: Write the failing test**

`tests/Feature/NewsArticleTest.php`:

```php
<?php

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\User;

it('uses the author name as the byline when one is set', function () {
    $article = NewsArticle::factory()->create(['author_name' => 'Media Unit']);

    expect($article->byline())->toBe('Media Unit');
});

it('falls back to the creator name when no author is set', function () {
    $user = User::factory()->create(['name' => 'Jale Lomaloma']);

    $this->actingAs($user);
    $article = NewsArticle::factory()->create(['author_name' => null]);

    expect($article->fresh()->byline())->toBe('Jale Lomaloma');
});

it('belongs to a category', function () {
    $category = NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);
    $article = NewsArticle::factory()->create(['news_category_id' => $category->id]);

    expect($article->category->name)->toBe('Announcements');
});
```

- [ ] **Step 3: Run to verify it fails**

```powershell
./vendor/bin/pest --filter="NewsArticle"
```

Expected: FAIL — model not found.

- [ ] **Step 4: Create the model**

Same trait set as `Programme`, plus:

```php
public function category(): BelongsTo
{
    return $this->belongsTo(NewsCategory::class, 'news_category_id');
}

public function byline(): string
{
    return $this->author_name ?: ($this->creator?->name ?? 'Department of Environment');
}

public function seoFallbackDescription(): ?string
{
    return $this->excerpt;
}
```

Cast `share_to_facebook` to `boolean` and `facebook_posted_at` to `datetime`.

- [ ] **Step 5: Run the tests**

```powershell
php artisan migrate
./vendor/bin/pest --filter="NewsArticle"
```

Expected: PASS.

- [ ] **Step 6: Policy and Filament resource**

Copy the policy pattern. Then:

```powershell
php artisan make:filament-resource NewsArticle --generate
```

Add `Select::make('news_category_id')->relationship('category', 'name')->required()`, `TextInput::make('author_name')->helperText('Leave blank to use your own name as the byline.')`, and a `Toggle::make('share_to_facebook')->helperText('Facebook publishing is not yet connected. This flag is stored for a later phase.')->disabled()`.

The toggle is disabled deliberately: an enabled control that silently does nothing would mislead editors.

- [ ] **Step 7: Commit**

```powershell
git add -A
git commit -m "feat: news articles with category, byline and Facebook columns

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 11: Document library

**Files:**
- Create: migration `create_documents_table`, `app/Models/Document.php`, factory, `app/Policies/DocumentPolicy.php`, `app/Filament/Resources/Documents/*`
- Test: `tests/Feature/DocumentTest.php`

**Interfaces:**
- Consumes: `DocumentCategory` from Task 7, medialibrary from Task 8
- Produces: `Document` with `->category()`, `->file()`, `->fileSizeForHumans(): ?string`, `->fileType(): ?string`

- [ ] **Step 1: Create the migration**

```php
Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->foreignId('document_category_id')->nullable()->constrained('document_categories')->nullOnDelete();
    $table->date('published_date')->nullable();
    $table->boolean('is_featured')->default(false);
    $table->string('status')->default('draft')->index();
    $table->timestamp('published_at')->nullable()->index();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->boolean('is_demo')->default(false)->index();
    $table->timestamps();
});
```

No SEO columns — documents have no detail page in the current roadmap. The `slug` exists so a `/resources/{slug}` URL can be added in Spec 3 without a migration.

- [ ] **Step 2: Write the failing test**

`tests/Feature/DocumentTest.php`:

```php
<?php

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('reports file size and type from the attached file', function () {
    Storage::fake('public');

    $document = Document::factory()->create(['title' => 'State of the Environment Report']);
    $document->addMedia(UploadedFile::fake()->create('report.pdf', 250, 'application/pdf'))
        ->toMediaCollection('file');

    expect($document->fresh()->fileType())->toBe('PDF')
        ->and($document->fresh()->fileSizeForHumans())->toContain('KB');
});

it('returns null size when no file is attached', function () {
    $document = Document::factory()->create();

    expect($document->fileSizeForHumans())->toBeNull()
        ->and($document->fileType())->toBeNull();
});
```

- [ ] **Step 3: Run to verify it fails**

```powershell
./vendor/bin/pest --filter="Document"
```

Expected: FAIL — model not found.

- [ ] **Step 4: Create the model**

```php
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
        $this->addMediaCollection('file')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
```

Note `Document` does **not** use `HasSeo` — it has no SEO columns.

- [ ] **Step 5: Run the tests**

```powershell
php artisan migrate
php artisan storage:link
./vendor/bin/pest --filter="Document"
```

Expected: PASS.

- [ ] **Step 6: Filament resource with a strong upload field**

```powershell
php artisan make:filament-resource Document --generate
```

The file upload must constrain both type and size:

```php
SpatieMediaLibraryFileUpload::make('file')
    ->collection('file')
    ->acceptedFileTypes([
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ])
    ->maxSize(20480)
    ->required()
```

Add table columns for category, publication date, file type and file size so the library is scannable in the CMS.

- [ ] **Step 7: Commit**

```powershell
git add -A
git commit -m "feat: document library with automatic size and type extraction

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 12: Site and homepage settings

**Files:**
- Create: migrations `create_site_settings_table`, `create_homepage_settings_table`, `app/Models/SiteSetting.php`, `app/Models/HomepageSetting.php`, `app/Filament/Pages/ManageSiteSettings.php`, `app/Filament/Pages/ManageHomepage.php`, `database/seeders/SettingsSeeder.php`
- Test: `tests/Feature/SettingsTest.php`

**Interfaces:**
- Consumes: nothing
- Produces: `SiteSetting::current(): SiteSetting` and `HomepageSetting::current(): HomepageSetting`, both returning the single row, creating it if absent

- [ ] **Step 1: Create the migrations**

`create_site_settings_table`:

```php
Schema::create('site_settings', function (Blueprint $table) {
    $table->id();
    $table->string('department_name')->default('Niue Department of Environment');
    $table->string('government_name')->default('Government of Niue');
    $table->text('address')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->text('office_hours')->nullable();
    $table->string('facebook_url')->nullable();
    $table->string('youtube_url')->nullable();
    $table->text('footer_text')->nullable();
    $table->timestamps();
});
```

`create_homepage_settings_table`:

```php
Schema::create('homepage_settings', function (Blueprint $table) {
    $table->id();
    $table->string('hero_headline')->nullable();
    $table->text('hero_intro')->nullable();
    $table->string('hero_primary_cta_label')->nullable();
    $table->string('hero_primary_cta_url')->nullable();
    $table->string('hero_secondary_cta_label')->nullable();
    $table->string('hero_secondary_cta_url')->nullable();
    $table->string('quick_links_heading')->nullable();
    $table->string('programmes_heading')->nullable();
    $table->text('programmes_intro')->nullable();
    $table->string('news_heading')->nullable();
    $table->string('projects_heading')->nullable();
    $table->string('resources_heading')->nullable();
    $table->string('report_heading')->nullable();
    $table->text('report_intro')->nullable();
    $table->string('report_cta_label')->nullable();
    $table->string('report_cta_url')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 2: Write the failing test**

`tests/Feature/SettingsTest.php`:

```php
<?php

use App\Models\HomepageSetting;
use App\Models\SiteSetting;

it('returns a single site settings row, creating it when absent', function () {
    expect(SiteSetting::count())->toBe(0);

    $first = SiteSetting::current();
    $second = SiteSetting::current();

    expect(SiteSetting::count())->toBe(1)
        ->and($first->id)->toBe($second->id)
        ->and($first->department_name)->toBe('Niue Department of Environment');
});

it('returns a single homepage settings row', function () {
    HomepageSetting::current();
    HomepageSetting::current();

    expect(HomepageSetting::count())->toBe(1);
});
```

- [ ] **Step 3: Run to verify it fails**

```powershell
./vendor/bin/pest --filter="Settings"
```

Expected: FAIL — models not found.

- [ ] **Step 4: Create the models**

`app/Models/SiteSetting.php`:

```php
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
        return static::firstOrCreate([]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }
}
```

`app/Models/HomepageSetting.php` follows the same shape, with a `hero_image` single-file collection and `hero` conversion at 1920×900.

- [ ] **Step 5: Run the tests**

```powershell
php artisan migrate
./vendor/bin/pest --filter="Settings"
```

Expected: PASS.

- [ ] **Step 6: Seed the real homepage copy**

`database/seeders/SettingsSeeder.php` — this is real content, not demo content, so no `is_demo` flag:

```php
<?php

namespace Database\Seeders;

use App\Models\HomepageSetting;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::current()->update([
            'department_name' => 'Niue Department of Environment',
            'government_name' => 'Government of Niue',
            'address' => 'Alofi, Niue',
            'email' => 'environment@mail.gov.nu',
            'office_hours' => 'Monday to Friday, 8:00am – 4:00pm',
            'footer_text' => 'Protecting Niue\'s natural environment for future generations.',
        ]);

        HomepageSetting::current()->update([
            'hero_headline' => "Protecting Niue's Environment for Future Generations",
            'hero_intro' => 'Supporting conservation, biodiversity, sustainable waste management and responsible stewardship of Niue\'s natural environment.',
            'hero_primary_cta_label' => 'Explore Our Work',
            'hero_primary_cta_url' => '/environment-programmes',
            'hero_secondary_cta_label' => 'Latest News',
            'hero_secondary_cta_url' => '/news',
            'quick_links_heading' => 'Quick Links',
            'programmes_heading' => 'Our Environment Programmes',
            'programmes_intro' => 'The Department delivers programmes across conservation, waste, climate and biodiversity.',
            'news_heading' => 'Latest News',
            'projects_heading' => 'Featured Projects',
            'resources_heading' => 'Publications and Resources',
            'report_heading' => 'Report an Environmental Issue',
            'report_intro' => 'Help us protect Niue\'s environment. Report pollution, illegal dumping or damage to protected areas.',
            'report_cta_label' => 'Report an Issue',
            'report_cta_url' => '/report-an-environmental-issue',
        ]);
    }
}
```

Register in `DatabaseSeeder`. Note the CTA URLs point at routes built in Specs 2–4; in Spec 1 the footer and homepage render them, and they will 404 until those specs land. Record this in the Task 17 documentation.

- [ ] **Step 7: Create the Filament settings pages**

```powershell
php artisan make:filament-page ManageSiteSettings
php artisan make:filament-page ManageHomepage
```

Each is a single-record form page: load with `SiteSetting::current()` / `HomepageSetting::current()`, fill the form on mount, and save back to the same row. Group both under a `Website` navigation group. Restrict both to Super Admin and Website Manager with a `canAccess()` returning `auth()->user()->mayPublish()`.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: site and homepage settings with CMS pages

Typed single-row tables rather than key/value, so the columns are
self-documenting at handover and work with medialibrary.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 13: Quick links

**Files:**
- Create: migration `create_quick_links_table`, `app/Models/QuickLink.php`, `database/seeders/QuickLinkSeeder.php`, `app/Filament/Resources/QuickLinks/*`
- Test: `tests/Feature/QuickLinkTest.php`

**Interfaces:**
- Consumes: nothing
- Produces: `QuickLink` with an `active()` scope ordered by `sort_order`

- [ ] **Step 1: Create the migration**

```php
Schema::create('quick_links', function (Blueprint $table) {
    $table->id();
    $table->string('label');
    $table->string('description')->nullable();
    $table->string('icon')->nullable();      // Heroicons name, e.g. heroicon-o-globe-alt
    $table->string('url');
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

No `is_demo` column — these six links are real content.

- [ ] **Step 2: Write the failing test**

`tests/Feature/QuickLinkTest.php`:

```php
<?php

use App\Models\QuickLink;

it('returns active links in sort order', function () {
    QuickLink::create(['label' => 'Second', 'url' => '/b', 'sort_order' => 2]);
    QuickLink::create(['label' => 'First', 'url' => '/a', 'sort_order' => 1]);
    QuickLink::create(['label' => 'Hidden', 'url' => '/c', 'sort_order' => 0, 'is_active' => false]);

    expect(QuickLink::active()->pluck('label')->all())->toBe(['First', 'Second']);
});

it('seeds the six quick links from the brief', function () {
    $this->seed(\Database\Seeders\QuickLinkSeeder::class);

    expect(QuickLink::active()->count())->toBe(6);
});
```

- [ ] **Step 3: Run to verify it fails**

```powershell
./vendor/bin/pest --filter="QuickLink"
```

Expected: FAIL — model not found.

- [ ] **Step 4: Create the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuickLink extends Model
{
    protected $fillable = ['label', 'description', 'icon', 'url', 'sort_order', 'is_active'];

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

```php
<?php

namespace Database\Seeders;

use App\Models\QuickLink;
use Illuminate\Database\Seeder;

class QuickLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['label' => 'Environment Programmes', 'icon' => 'heroicon-o-globe-alt',        'url' => '/environment-programmes',        'description' => 'Conservation, waste, climate and biodiversity work.'],
            ['label' => 'Waste & Recycling',      'icon' => 'heroicon-o-trash',            'url' => '/waste-and-recycling',           'description' => 'Waste services, recycling and disposal guidance.'],
            ['label' => 'Biodiversity',           'icon' => 'heroicon-o-sparkles',         'url' => '/biodiversity-and-conservation', 'description' => 'Protecting Niue\'s native species and habitats.'],
            ['label' => 'Climate & Marine',       'icon' => 'heroicon-o-cloud',            'url' => '/climate-and-marine',            'description' => 'Climate resilience and marine protection.'],
            ['label' => 'Publications',           'icon' => 'heroicon-o-document-text',    'url' => '/resources',                     'description' => 'Reports, policies, legislation and forms.'],
            ['label' => 'Report an Issue',        'icon' => 'heroicon-o-exclamation-triangle', 'url' => '/report-an-environmental-issue', 'description' => 'Tell us about pollution or environmental damage.'],
        ];

        foreach ($links as $index => $link) {
            QuickLink::updateOrCreate(
                ['url' => $link['url']],
                [...$link, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
```

Register in `DatabaseSeeder`.

- [ ] **Step 6: Run the tests and generate the resource**

```powershell
php artisan migrate
./vendor/bin/pest --filter="QuickLink"
php artisan make:filament-resource QuickLink --generate
```

Expected: PASS. Make the resource table reorderable on `sort_order`, and present `icon` as a `Select` of Heroicons names rather than free text.

- [ ] **Step 7: Commit**

```powershell
git add -A
git commit -m "feat: reorderable homepage quick links

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 14: Demo content and the purge command

**Files:**
- Create: `database/seeders/DemoContentSeeder.php`, `app/Console/Commands/PurgeDemoContent.php`, `database/seeders/demo-images/` (CC0 photographs), `docs/LICENSES.md`
- Test: `tests/Feature/DemoContentTest.php`

**Interfaces:**
- Consumes: all content models from Tasks 8–11
- Produces: `php artisan demo:purge`, and seeded content where every record has `is_demo = true`

> **⚠ HUMAN ACTION IN THIS TASK.** The CC0 photographs must be sourced by a human who verifies each licence. An agent must not download images from arbitrary URLs and assert they are CC0.

- [ ] **Step 1: Source the demo photographs**

Obtain 8–12 genuinely CC0 / public-domain photographs covering ocean, coastline, reef, forest, waste management and community conservation. Suitable sources are the CC0 collections on Openverse, Wikimedia Commons files explicitly marked CC0 or public domain, and Unsplash+ CC0-era images whose licence you have confirmed individually.

Save to `database/seeders/demo-images/` with descriptive names (`coastline-01.jpg`, `reef-02.jpg`).

- [ ] **Step 2: Record provenance**

Create `docs/LICENSES.md`:

```markdown
# Demo Image Licences

Every image in `database/seeders/demo-images/` is placeholder content for
development only. All are CC0 or public domain. None originate from the Fiji
Environment website or any other government site.

These images are seeded with `is_demo = true` and are removed by
`php artisan demo:purge` before the site carries real Department photography.

| File | Source URL | Licence | Verified by | Date |
|------|-----------|---------|-------------|------|
| coastline-01.jpg | <url> | CC0 | <name> | 2026-08-07 |
```

Fill a row per image. An unverified image does not go in the repository.

- [ ] **Step 3: Write the failing test**

`tests/Feature/DemoContentTest.php`:

```php
<?php

use App\Models\Document;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;

it('flags every seeded record as demo content', function () {
    $this->seed(\Database\Seeders\DemoContentSeeder::class);

    expect(Programme::where('is_demo', false)->count())->toBe(0)
        ->and(NewsArticle::where('is_demo', false)->count())->toBe(0)
        ->and(Project::where('is_demo', false)->count())->toBe(0)
        ->and(Document::where('is_demo', false)->count())->toBe(0);
});

it('removes all demo content and leaves real content alone', function () {
    $this->seed(\Database\Seeders\DemoContentSeeder::class);
    $real = Programme::factory()->create(['is_demo' => false, 'title' => 'Real programme']);

    expect(Programme::count())->toBeGreaterThan(1);

    $this->artisan('demo:purge', ['--force' => true])->assertSuccessful();

    expect(Programme::count())->toBe(1)
        ->and(Programme::first()->title)->toBe('Real programme')
        ->and(NewsArticle::count())->toBe(0)
        ->and(Document::count())->toBe(0);
});
```

- [ ] **Step 4: Run to verify it fails**

```powershell
./vendor/bin/pest --filter="Demo"
```

Expected: FAIL — seeder and command do not exist.

- [ ] **Step 5: Write the demo seeder**

`database/seeders/DemoContentSeeder.php` creates six programmes matching the brief (Conservation, Waste Management, Marine Conservation, Biodiversity, Climate Resilience, Community Clean-Up), four to six news articles across categories, three to four projects linked to programmes, and six documents across the seeded categories. Every record sets `is_demo => true`, `status => ContentStatus::Published` and `published_at => now()->subDays(n)`.

Attach images with `addMedia(database_path('seeders/demo-images/<file>'))->preservingOriginal()->toMediaCollection('featured_image')`, setting the `alt` custom property on each — alt text is required everywhere, including seeds.

Mark at least two programmes, two projects and three documents `is_featured => true` so every homepage section has content.

- [ ] **Step 6: Write the purge command**

`app/Console/Commands/PurgeDemoContent.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;
use Illuminate\Console\Command;

class PurgeDemoContent extends Command
{
    protected $signature = 'demo:purge {--force : Skip confirmation}';

    protected $description = 'Permanently delete all content flagged is_demo';

    /** Projects first: they reference programmes. */
    private const MODELS = [Project::class, NewsArticle::class, Document::class, Programme::class];

    public function handle(): int
    {
        $total = collect(self::MODELS)->sum(fn (string $model) => $model::where('is_demo', true)->count());

        if ($total === 0) {
            $this->info('No demo content found.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Permanently delete {$total} demo records?")) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        foreach (self::MODELS as $model) {
            // Delete individually so medialibrary removes the attached files too.
            $model::where('is_demo', true)->cursor()->each->delete();
        }

        $this->info("Deleted {$total} demo records and their media.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 7: Run the tests and seed for real**

```powershell
./vendor/bin/pest --filter="Demo"
php artisan migrate:fresh --seed
```

Expected: both tests PASS; the database now holds roles, categories, settings, quick links and demo content.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: demo content seeder with CC0 imagery and demo:purge

Every seeded record carries is_demo=true. Image provenance recorded in
docs/LICENSES.md; nothing sourced from the Fiji Environment website.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 15: Site header, footer and error pages

**Files:**
- Create: `config/navigation.php`, `resources/views/components/site/header.blade.php`, `resources/views/components/site/footer.blade.php`, `resources/views/components/ui/{button,card,section}.blade.php`, `resources/views/errors/{403,404,500}.blade.php`
- Modify: `resources/js/app.js`, `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/SiteChromeTest.php`

**Interfaces:**
- Consumes: `SiteSetting::current()` from Task 12
- Produces: `<x-site.header>`, `<x-site.footer>`, `<x-ui.button variant="primary|secondary" href="">`, `<x-ui.card>`, `<x-ui.section heading="" intro="">`

- [ ] **Step 1: Define the fixed information architecture**

`config/navigation.php` — Spec 1 uses fixed IA; database-driven navigation is Spec 2:

```php
<?php

return [
    'primary' => [
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
    ],
];
```

- [ ] **Step 2: Write the failing test**

`tests/Feature/SiteChromeTest.php`:

```php
<?php

use App\Models\SiteSetting;

beforeEach(function () {
    SiteSetting::current()->update([
        'department_name' => 'Niue Department of Environment',
        'government_name' => 'Government of Niue',
        'email' => 'environment@mail.gov.nu',
    ]);
});

it('shows the government identity and contact details from the database', function () {
    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertSee('Niue Department of Environment');
    $response->assertSee('Government of Niue');
    $response->assertSee('environment@mail.gov.nu');
});

it('marks the current navigation item with aria-current', function () {
    $this->withoutVite()->get('/')->assertSee('aria-current="page"', false);
});

it('renders a branded 404 page', function () {
    $this->withoutVite()->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Page not found');
});
```

- [ ] **Step 3: Run to verify it fails**

```powershell
./vendor/bin/pest --filter="SiteChrome"
```

Expected: FAIL — `/` still serves the default Laravel welcome view.

- [ ] **Step 4: Share settings with every view**

In `app/Providers/AppServiceProvider::boot()`:

```php
use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;

View::composer('components.site.*', function ($view) {
    $view->with('settings', SiteSetting::current());
});
```

- [ ] **Step 5: Build the header**

`resources/views/components/site/header.blade.php`. Requirements: a government identity bar carrying the department and government names on `brand` with white text; primary navigation beneath; the active item marked with `aria-current="page"` **and** an accent underline (`border-b-2 border-accent`) — never yellow text; a mobile menu button with `aria-expanded` and `aria-controls`; `<nav aria-label="Primary">`. There is no search control in Spec 1.

```blade
<header class="on-dark">
    <div class="bg-brand text-white">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4">
            <div>
                <p class="text-lg font-bold leading-tight">{{ $settings->department_name }}</p>
                <p class="text-sm text-white/80">{{ $settings->government_name }}</p>
            </div>

            <button type="button"
                    id="menu-toggle"
                    aria-expanded="false"
                    aria-controls="primary-nav"
                    class="ml-auto rounded p-2 lg:hidden">
                <span class="sr-only">Open main menu</span>
                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6 stroke-current" fill="none" stroke-width="2">
                    <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" />
                </svg>
            </button>
        </div>
    </div>

    <nav aria-label="Primary" class="border-b border-black/10 bg-white">
        <ul id="primary-nav" class="mx-auto hidden max-w-7xl flex-wrap px-4 lg:flex">
            @foreach (config('navigation.primary') as $item)
                <li>
                    <a href="{{ $item['url'] }}"
                       @if (request()->is(ltrim($item['url'], '/') ?: '/')) aria-current="page" @endif
                       @class([
                           'inline-flex min-h-11 items-center border-b-2 px-3 py-2 text-sm font-semibold text-ink hover:border-accent',
                           'border-accent' => request()->is(ltrim($item['url'], '/') ?: '/'),
                           'border-transparent' => ! request()->is(ltrim($item['url'], '/') ?: '/'),
                       ])>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</header>
```

- [ ] **Step 6: Build the mobile menu in vanilla JavaScript**

Replace `resources/js/app.js`:

```js
// Mobile navigation disclosure. Deliberately framework-free: the public
// site ships no Alpine, React or Vue.
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('menu-toggle');
    const nav = document.getElementById('primary-nav');

    if (!toggle || !nav) return;

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';

        toggle.setAttribute('aria-expanded', String(!isOpen));
        nav.classList.toggle('hidden', isOpen);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            toggle.setAttribute('aria-expanded', 'false');
            nav.classList.add('hidden');
            toggle.focus();
        }
    });
});
```

- [ ] **Step 7: Build the footer and UI components**

The footer carries department contact details from `$settings`, a Government of Niue column, an Environment Programmes column, and a Resources column. **Only render links whose targets exist in Spec 1** — Privacy, Terms and Accessibility arrive in Spec 2 and are omitted for now rather than rendered dead.

`<x-ui.button>` enforces the yellow rule in code:

```blade
@props(['variant' => 'primary', 'href' => null])

@php
$classes = match ($variant) {
    // Yellow is never a button background with white text; the accent
    // variant always carries ink text.
    'primary'   => 'bg-brand text-white hover:bg-brand/90',
    'secondary' => 'bg-white text-brand border-2 border-brand hover:bg-brand/5',
    'accent'    => 'bg-accent text-ink hover:bg-accent/90',
};
@endphp

<{{ $href ? 'a' : 'button' }}
    @if ($href) href="{{ $href }}" @else type="button" @endif
    {{ $attributes->class(['inline-flex min-h-11 items-center justify-center rounded px-6 py-3 font-semibold', $classes]) }}>
    {{ $slot }}
</{{ $href ? 'a' : 'button' }}>
```

- [ ] **Step 8: Create the error pages**

`resources/views/errors/404.blade.php` uses `<x-layouts.public title="Page not found">` and includes the literal text `Page not found` plus a link home. Mirror for 403 (`Access denied`) and 500 (`Something went wrong`).

- [ ] **Step 9: Point `/` at a temporary route so the tests can run**

In `routes/web.php`:

```php
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
```

Create `app/Http/Controllers/HomeController.php` returning `view('home')` and a minimal `resources/views/home.blade.php` wrapping `<x-layouts.public>`. Task 16 fills it in.

- [ ] **Step 10: Run the tests**

```powershell
./vendor/bin/pest --filter="SiteChrome"
npm run build
```

Expected: all three PASS.

- [ ] **Step 11: Commit**

```powershell
git add -A
git commit -m "feat: site header, footer, UI components and error pages

Mobile menu is vanilla JS with aria-expanded; no framework on the public
site. Button component encodes the yellow-contrast rule.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 16: The homepage

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`, `resources/views/home.blade.php`
- Create: `resources/views/components/content/{news-card,programme-card,document-row}.blade.php`
- Test: `tests/Feature/HomepageTest.php`

**Interfaces:**
- Consumes: every model from Tasks 8–13
- Produces: the `home` route, rendering seven sections each of which hides entirely when empty

- [ ] **Step 1: Write the failing tests**

`tests/Feature/HomepageTest.php`:

```php
<?php

use App\Enums\ContentStatus;
use App\Models\HomepageSetting;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\QuickLink;

it('renders hero content from the database', function () {
    HomepageSetting::current()->update([
        'hero_headline' => "Protecting Niue's Environment for Future Generations",
        'hero_intro' => 'Supporting conservation and biodiversity.',
        'hero_primary_cta_label' => 'Explore Our Work',
    ]);

    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertSee("Protecting Niue's Environment for Future Generations", false);
    $response->assertSee('Explore Our Work');
});

it('shows published programmes and hides unpublished ones', function () {
    Programme::factory()->create([
        'title' => 'Marine Conservation Programme',
        'is_featured' => true,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    Programme::factory()->create([
        'title' => 'Secret Draft Programme',
        'is_featured' => true,
        'status' => ContentStatus::Draft,
    ]);

    $response = $this->withoutVite()->get('/');

    $response->assertSee('Marine Conservation Programme');
    $response->assertDontSee('Secret Draft Programme');
});

it('shows at most four news articles, newest first', function () {
    foreach (range(1, 6) as $i) {
        NewsArticle::factory()->create([
            'title' => "Article {$i}",
            'status' => ContentStatus::Published,
            'published_at' => now()->subDays(10 - $i),
        ]);
    }

    $response = $this->withoutVite()->get('/');

    $response->assertSee('Article 6');
    $response->assertDontSee('Article 1');
});

it('hides a section entirely when it has no content', function () {
    HomepageSetting::current()->update(['news_heading' => 'Latest News']);

    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertDontSee('Latest News');
});

it('renders without error on a completely empty database', function () {
    $this->withoutVite()->get('/')->assertOk();
});

it('renders active quick links', function () {
    QuickLink::create(['label' => 'Waste & Recycling', 'url' => '/waste-and-recycling', 'sort_order' => 1]);
    QuickLink::create(['label' => 'Hidden Link', 'url' => '/hidden', 'sort_order' => 2, 'is_active' => false]);

    $response = $this->withoutVite()->get('/');

    $response->assertSee('Waste &amp; Recycling', false);
    $response->assertDontSee('Hidden Link');
});
```

- [ ] **Step 2: Run to verify they fail**

```powershell
./vendor/bin/pest --filter="Homepage"
```

Expected: FAIL — the placeholder home view renders none of this.

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/HomeController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\HomepageSetting;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;
use App\Models\QuickLink;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'homepage' => HomepageSetting::current(),
            'quickLinks' => QuickLink::active()->get(),
            'programmes' => $this->featuredOrLatest(Programme::query(), 4),
            'news' => NewsArticle::published()->with('category')->latest('published_at')->take(4)->get(),
            'projects' => $this->featuredOrLatest(Project::query(), 3),
            'documents' => Document::published()->with('category')->latest('published_date')->take(5)->get(),
        ]);
    }

    /**
     * Featured items first; if nothing is flagged, fall back to the most
     * recent so a section never sits empty merely because nobody ticked a box.
     */
    private function featuredOrLatest(\Illuminate\Database\Eloquent\Builder $query, int $limit)
    {
        $featured = (clone $query)->published()->featured()->orderBy('sort_order')->take($limit)->get();

        return $featured->isNotEmpty()
            ? $featured
            : $query->published()->latest('published_at')->take($limit)->get();
    }
}
```

`Project` has no `sort_order` column, so use a `Project`-specific ordering — replace `orderBy('sort_order')` with `latest('published_at')` when the query is on `Project`. Simplest correct approach: give `featuredOrLatest` a `?string $order = null` parameter and pass `'sort_order'` for programmes only.

- [ ] **Step 4: Build the homepage view**

`resources/views/home.blade.php` renders, in order: hero, quick links, programmes, news, projects, resources, report CTA.

Every section is wrapped in a guard so it disappears completely — heading included — when empty:

```blade
<x-layouts.public :description="$homepage->hero_intro">

    {{-- Hero. Static image, no carousel: LCP and accessibility. --}}
    <section class="on-dark relative bg-ink text-white">
        @if ($hero = $homepage->getFirstMediaUrl('hero_image', 'hero'))
            <img src="{{ $hero }}" alt="" aria-hidden="true"
                 class="absolute inset-0 h-full w-full object-cover opacity-40">
        @endif

        <div class="relative mx-auto max-w-4xl px-4 py-20 text-center sm:py-28">
            <h1 class="text-3xl font-bold leading-tight sm:text-5xl">
                {{ $homepage->hero_headline }}
            </h1>

            @if ($homepage->hero_intro)
                <p class="mx-auto mt-6 max-w-2xl text-lg text-white/90">{{ $homepage->hero_intro }}</p>
            @endif

            <div class="mt-8 flex flex-wrap justify-center gap-4">
                @if ($homepage->hero_primary_cta_label)
                    <x-ui.button variant="accent" :href="$homepage->hero_primary_cta_url">
                        {{ $homepage->hero_primary_cta_label }}
                    </x-ui.button>
                @endif

                @if ($homepage->hero_secondary_cta_label)
                    <x-ui.button variant="secondary" :href="$homepage->hero_secondary_cta_url">
                        {{ $homepage->hero_secondary_cta_label }}
                    </x-ui.button>
                @endif
            </div>
        </div>
    </section>

    @if ($quickLinks->isNotEmpty())
        <x-ui.section :heading="$homepage->quick_links_heading">
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($quickLinks as $link)
                    <li>
                        <a href="{{ $link->url }}"
                           class="block h-full rounded border border-black/10 bg-white p-6 hover:border-brand">
                            <span class="block font-semibold text-brand">{{ $link->label }}</span>
                            @if ($link->description)
                                <span class="mt-1 block text-sm">{{ $link->description }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($programmes->isNotEmpty())
        <x-ui.section :heading="$homepage->programmes_heading" :intro="$homepage->programmes_intro">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($programmes as $programme)
                    <x-content.programme-card :programme="$programme" />
                @endforeach
            </div>
        </x-ui.section>
    @endif

    @if ($news->isNotEmpty())
        <x-ui.section :heading="$homepage->news_heading">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($news as $article)
                    <x-content.news-card :article="$article" />
                @endforeach
            </div>
        </x-ui.section>
    @endif

    {{-- Projects, resources and the report CTA follow the same guard pattern. --}}
</x-layouts.public>
```

Complete the projects section (using `$projects` and `$homepage->projects_heading`), the resources section (a list of `<x-content.document-row>` showing title, category, publication date, file type, file size and a download button), and the report CTA section (`$homepage->report_heading`, `report_intro`, and an `<x-ui.button variant="accent">` to `report_cta_url`).

- [ ] **Step 5: Build the content components**

`<x-content.news-card>` shows the featured image (with its stored alt text), the publication date in `d M Y`, the category name, the headline as an `<h3>`, the excerpt, and a "Read more" link whose accessible name includes the headline — never a bare "Read more". Since news detail pages are Spec 3, link to `#` and add a `TODO(spec-3)` comment naming the route that will replace it.

`<x-content.programme-card>` shows image, title, summary and link.

`<x-content.document-row>` shows title, category, publication date, `fileType()`, `fileSizeForHumans()`, and a download button pointing at `downloadUrl()`.

A missing image in any of these renders a `bg-brand/10` block rather than a broken `<img>`.

- [ ] **Step 6: Run the tests**

```powershell
./vendor/bin/pest --filter="Homepage"
```

Expected: all seven PASS. The `renders without error on a completely empty database` test is the one most likely to fail — it catches null dereferences on settings that have never been saved.

- [ ] **Step 7: Run the full suite**

```powershell
./vendor/bin/pest
```

Expected: everything green.

- [ ] **Step 8: View it**

```powershell
npm run build
```

Open `https://niue-doe-website.test` and confirm the homepage renders with seeded content.

- [ ] **Step 9: Commit**

```powershell
git add -A
git commit -m "feat: database-driven homepage with empty-state handling

Sections hide entirely when they have no content, heading included.
Featured items first, falling back to most recent.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Task 17: Accessibility verification and documentation

**Files:**
- Create: `README.md`, `docs/architecture.md`, `docs/cms-guide.md`, `docs/deployment-readiness.md`, `docs/social-media-integration.md`
- Test: `tests/Feature/AccessibilityTest.php`

**Interfaces:**
- Consumes: the completed homepage
- Produces: the handover documentation set

- [ ] **Step 1: Write the accessibility assertions**

`tests/Feature/AccessibilityTest.php`:

```php
<?php

use App\Models\Programme;

it('has exactly one h1 on the homepage', function () {
    $html = $this->withoutVite()->get('/')->getContent();

    expect(substr_count($html, '<h1'))->toBe(1);
});

it('exposes a skip link and a main landmark', function () {
    $response = $this->withoutVite()->get('/');

    $response->assertSee('Skip to main content');
    $response->assertSee('<main id="main"', false);
});

it('gives every content image a non-empty alt attribute', function () {
    Programme::factory()->create(['is_featured' => true]);

    $html = $this->withoutVite()->get('/')->getContent();

    // Decorative images are aria-hidden with alt=""; content images must not be.
    preg_match_all('/<img(?![^>]*aria-hidden)[^>]*>/i', $html, $matches);

    foreach ($matches[0] as $img) {
        expect($img)->toMatch('/alt="[^"]+"/');
    }
});
```

- [ ] **Step 2: Run and fix**

```powershell
./vendor/bin/pest --filter="Accessibility"
```

Expected: PASS. Fix any content image lacking alt text at its source — the Filament upload field, not the Blade template.

- [ ] **Step 3: Manual verification checklist**

Perform and record the result of each:

- Tab through the homepage from the top: the skip link appears first, every interactive element shows a visible focus ring, and focus order matches visual order.
- Narrow the browser to 360px: no horizontal scroll, the mobile menu opens and closes, and Escape closes it.
- Confirm no yellow text appears on a white background anywhere.
- Confirm the hero has no animation and nothing auto-plays.

- [ ] **Step 4: Write the documentation**

`README.md` — what the project is, the verified version floors, local setup from a clean machine (Herd, PostgreSQL, `createdb`, `.env`, `migrate --seed`, `npm run build`, `herd link`), how to run tests, and a prominent note that **no deployment is authorised**.

`docs/architecture.md` — the trait system and why `published()` is the only sanctioned public query, the `status` versus `project_status` rule, the settings-table approach, the media collections, and the role model.

`docs/cms-guide.md` — written for Department staff, not developers: how to log in, the four workflow states and who may publish, how to add a news article, how to upload a document, how to change the hero, how to reorder quick links, and why alt text is mandatory.

`docs/deployment-readiness.md` — what exists and what remains before production: queue driver, mail driver, session and cache stores, `APP_DEBUG=false`, storage permissions, the Cloudflare → DigitalOcean → Laravel → PostgreSQL target shape, and an explicit note that deployment requires separate authorisation.

`docs/social-media-integration.md` — the three `news_articles` Facebook columns that exist today, the intended `publish → queue job → Meta Graph API → Page post` flow, the `SocialPublisher` interface to be introduced in Spec 5, and exactly what is still required: a Meta app, Page access token, `pages_manage_posts` permission and App Review.

- [ ] **Step 5: Record the known dead links**

In `README.md`, note that the seeded CTA and navigation URLs point at routes built in Specs 2–4 and will 404 until then. This is expected in Spec 1 and must not be "fixed" by removing the links.

- [ ] **Step 6: Run the full suite one final time**

```powershell
./vendor/bin/pest
npm run build
```

Expected: all tests pass, build clean.

- [ ] **Step 7: Commit**

```powershell
git add -A
git commit -m "docs: handover documentation and accessibility verification

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Self-Review

**Spec coverage check.** Every section of the spec maps to a task:

| Spec section | Task |
|---|---|
| 3 Environment | 1, 2 |
| 4.1 Skeleton, no starter kit | 2 |
| 4.2 Dependencies | 4, 5, 8 |
| 4.3 Roles | 5 |
| 4.4 Traits | 6 |
| 5.1 Content tables | 7–11 |
| 5.2 Configuration tables | 12, 13 |
| 5.3 Relationships | 8, 9, 10, 11 |
| 5.4 Recorded decisions | 7 (categories), 9 (`project_status`), 10 (byline), 14 (`is_demo`) |
| 6 Demo content | 14 |
| 7.1–7.3 Tokens, yellow rule, typography | 3, 15 |
| 7.4 Components | 15, 16 |
| 7.5 Homepage | 16 |
| 7.6 Accessibility | 3, 15, 16, 17 |
| 7.7 Error handling | 15, 16 |
| 8 Security | 5, 8–11 (policies), 11 (upload limits) |
| 9 Testing | 6, 8, 16, 17 |
| 10 Documentation | 17 |
| 11 Exclusions | 15 (no search control, no dead footer links) |
| 12 Success criteria | 16, 17 |

No gaps.

**Type consistency.** `published()`, `featured()`, `isPublished()`, `seoTitle()`, `seoDescription()`, `seoFallbackDescription()`, `byline()`, `fileSizeForHumans()`, `fileType()`, `downloadUrl()`, `current()`, `active()`, `mayPublish()`, `canPublish()` and `canAccessPanel()` are each defined once and used with the same name and signature throughout.

**Two issues found and fixed inline during review:**

1. Task 16's `featuredOrLatest()` originally ordered by `sort_order` for both programmes and projects, but `projects` has no `sort_order` column — this would have thrown a Postgres `column does not exist` error at runtime. Step 3 now calls it out and specifies the parameterised ordering.
2. Task 6's scope tests reference `Programme`, which does not exist until Task 8. Rather than move the tests, Task 6 Step 1 states explicitly that they are written there and first run green in Task 8 Step 5, with a hard stop if they fail.

**One risk to flag, not fixable in the plan:** Filament 5.7 post-dates my training data. Task snippets use Filament 4/5 conventions, and the Global Constraints make the code generator authoritative wherever a signature differs. Expect to adapt form-schema snippets to the generated method signatures; that is anticipated, not a defect.
