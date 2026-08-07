# Design Spec — Niue Department of Environment Website: Foundation & Homepage

**Date:** 2026-08-07
**Spec:** 1 of 6
**Status:** Approved
**Author:** Jale Lomaloma / Siale Designs & Multimedia
**Client:** Niue Department of Environment, Government of Niue

---

## 1. Purpose

Establish the technical foundation for the Niue Department of Environment website and
deliver a fully database-driven homepage, so that the visual direction and the CMS
experience can both be judged before the remaining information architecture is built.

The overriding architectural constraint is **handover**. Siale Designs & Multimedia will
operate the site initially, uploading content supplied and approved by the Department.
The Department must later be able to take over content management entirely, through the
CMS, without the website being rebuilt. Every decision in this spec is measured against
that requirement.

---

## 2. Scope decomposition

The full brief describes six independent subsystems. It is too large for one spec, so it
is split into six, each delivered as a **vertical slice** — model, CMS resource and public
rendering shipped together, so no feature is ever half-wired.

| Spec | Delivers |
|---|---|
| **1 (this document)** | Environment, Laravel 13 + Filament 5, design system, auth + roles, global layout, homepage, and the five content types the homepage consumes |
| 2 | Pages content model, About / Mandate / Mission / Team, the four topic sections, Contact, database-driven navigation |
| 3 | News index & detail, Projects detail, Resources browse/filter, site search, Gallery, Events, Vacancies |
| 4 | Contact form, Report an Environmental Issue form, admin submission inboxes |
| 5 | Internal dashboard, full SEO (Open Graph, canonicals, sitemap, robots), Facebook publishing abstraction and queue |
| 6 | Accessibility audit, security review, responsive and performance testing, CMS usability, client demonstration |

This deliberately departs from the phase list in the brief in one respect: models and
their CMS resources are never separated across specs. Creating a model in one phase and
its admin interface in a later one leaves a window where content exists but nobody can
enter it.

---

## 3. Environment

**Local development only.** No DigitalOcean resources, no DNS changes, no Cloudflare
configuration, no production database, no production credentials, no external service
changes. Production deployment is a separate, separately authorised phase.

| Component | Choice | Status on the workstation |
|---|---|---|
| PHP 8.4 | Laravel Herd (Windows) | To install |
| Composer | Bundled with Herd | To install |
| PostgreSQL 18 | winget, local database `niue_doe` | To install |
| Node 24 / npm 11 | Already present (v24.18.0 / 11.16.0) | ✅ |
| Git 2.55 | Already present | ✅ |

Herd serves the site at `https://niue-doe-website.test`.

`.env` is git-ignored. `.env.example` is maintained as the handover contract and must
stay current — it is the document a future developer reads to understand what the
application needs.

### Version compatibility — verified, not assumed

Checked against the Packagist API on 2026-08-06:

- `laravel/framework` **v13.24.0** (2026-08-04) requires PHP `^8.3`
- `filament/filament` **v5.7.6** (2026-08-05) requires PHP `^8.2`
- `filament/support` v5.7.6 declares `illuminate/contracts: ^11.28|^12.0|^13.0`

**Filament 5 explicitly supports Laravel 13.** Filament 5 pulls Livewire 4, which is
confined to the admin panel; the public site remains pure server-rendered Blade.

---

## 4. Architecture

### 4.1 Application skeleton

Plain `laravel/laravel` v13 with **no starter kit**. Laravel 13's starter kits scaffold
user-facing authentication in React, Vue or Livewire, none of which is wanted.

**The public site has no user accounts and no login.** The only authentication in the
system is the Filament admin panel at `/admin`. This is a deliberate reduction in attack
surface appropriate to a government information site.

### 4.2 Dependencies beyond the framework

| Concern | Package | Rationale |
|---|---|---|
| Roles | `spatie/laravel-permission` (roles only) | Four named roles, and Department staff must be granted a role through the UI later without a code change. A plain enum column would work but requires hand-rolled assignment UI and repeated role checks in every policy. |
| Media | `spatie/laravel-medialibrary` | Extracts file size and MIME type automatically — a stated requirement of the document library — and generates responsive image conversions, so one upload serves a 1920px hero and a 400px card thumbnail. Bandwidth to Niue makes this material, not cosmetic. |
| Admin | `filament/filament` ^5.7 | CMS panel |

**Filament Shield is deliberately not used.** It generates a permission per resource per
action, which is enterprise-scale noise for a modest government site. Roles are coarse
and policies are written by hand.

### 4.3 Roles

| Role | Capability |
|---|---|
| Super Admin | Everything, including user management and settings |
| Website Manager | All content, settings, and submissions; cannot manage users |
| Editor | Create and edit content; can move Draft → Under Review; cannot publish |
| Viewer | Read-only access to the panel and submissions |

Siale Designs operates initially as Super Admin and Website Manager. Department staff are
later granted Website Manager or Editor.

### 4.4 Cross-cutting concerns

Implemented once as traits, never repeated per model.

**`HasStatus`** — the `ContentStatus` enum (`Draft`, `UnderReview`, `Published`,
`Archived`) plus a `published()` scope meaning *status is Published **and** `published_at`
is not in the future*.

Every public query goes through this scope. Centralising it is the difference between an
editor's draft leaking to the public being impossible, versus being one forgotten `where`
clause away. This is covered by an explicit test.

**`HasBlame`** — `created_by` and `updated_by`, auto-populated on save, exposing
`creator()` and `editor()` relations.

**`HasSeo`** — `seo_title` and `seo_description` as nullable columns, plus an `og_image`
single-file medialibrary collection that falls back to the model's featured image when
empty. The columns land now so there is no migration churn later. Spec 1 renders only a
dynamic `<title>` and meta description; Open Graph, canonicals and the sitemap are Spec 5.

---

## 5. Data model

Nine application tables beyond Laravel's defaults, plus Spatie's role tables. Every table
below receives a working Filament resource in Spec 1.

### 5.1 Content tables

All carry `HasStatus`, `HasBlame`, `HasSeo`, and an `is_demo` boolean, unless noted.

**`programmes`**
`id, title, slug, summary, body, is_featured, sort_order, status, published_at,
seo_title, seo_description, og_image, created_by, updated_by, is_demo, timestamps`
Featured image via medialibrary.

**`projects`**
`id, title, slug, summary, body, programme_id (nullable FK), project_status, start_date,
end_date, is_featured, status, published_at, seo_*, created_by, updated_by, is_demo,
timestamps`
Featured image via medialibrary.

**`news_articles`**
`id, title, slug, excerpt, body, news_category_id (FK), author_name (nullable),
is_featured, status, published_at, share_to_facebook, facebook_posted_at,
facebook_post_id, seo_*, created_by, updated_by, is_demo, timestamps`
Featured image via medialibrary. The three Facebook columns exist from the start; the
integration itself is Spec 5.

**`documents`**
`id, title, slug, description, document_category_id (FK), published_date, is_featured,
status, published_at, created_by, updated_by, is_demo, timestamps`
File attached via medialibrary, which supplies file size and MIME type automatically.
No SEO columns — documents have no detail page in the current roadmap, and the public
interaction is a direct download. The `slug` column exists anyway so a stable
`/resources/{slug}` detail URL can be added in Spec 3 without a migration.

**`news_categories`** — `id, name, slug, sort_order, timestamps`. No workflow status.

**`document_categories`** — `id, name, slug, sort_order, timestamps`. No workflow status.
Seeded with Publications, Policies, Legislation, Forms, Reports, Downloads.

### 5.2 Configuration tables

Single-row tables edited through custom Filament pages. Typed columns rather than a
key/value store: they are self-documenting for handover and work directly with
medialibrary for image fields.

**`site_settings`** — department name, address, phone, email, office hours, social media
URLs, footer text.

**`homepage_settings`** — hero headline, hero intro, hero background image, primary CTA
label and target, secondary CTA label and target, and the heading plus intro for each
homepage section.

**`quick_links`** — `id, label, description, icon, url, sort_order, is_active,
timestamps`. `icon` stores a Heroicons name (the icon set Filament already ships), chosen
from a select field rather than typed freehand. Seeded with the six cards from the brief;
reorderable in the CMS. These are real content, not demo content, so this table has no
`is_demo` column.

### 5.3 Relationships

- `Programme` hasMany `Project`
- `NewsCategory` hasMany `NewsArticle`
- `DocumentCategory` hasMany `Document`
- `User` hasMany created/edited content via `HasBlame`
- Media attached polymorphically by medialibrary

### 5.4 Decisions recorded

**Document categories are a table, not an enum.** The brief lists Publications, Policies,
Legislation, Forms, Reports and Downloads as separate CMS items, but they are structurally
identical. Six near-duplicate Filament resources would be waste. One `documents` model
with an editable category table gives the same public browsing experience and lets the
Department add a category later without a developer.

**`status` versus `project_status`.** Projects carry two unrelated state concepts: the
editorial workflow (Draft → Published) and the real-world lifecycle (Planned, Active,
Completed, On Hold). Naming both `status` invites bugs. Across every model, the editorial
workflow is always `status`; the project lifecycle is always `project_status`.

**Author is a free-text `author_name`, not a foreign key.** Content arrives from the
Department already approved and is uploaded by Siale Designs, so the user in `created_by`
is usually not the byline. A nullable string falling back to the creator's name handles
"Department of Environment" or "Media Unit" as authors without creating user accounts for
people who will never log in.

**`is_demo` on every content table.** Seeded records are flagged, badged in Filament, and
removable with `php artisan demo:purge`. This satisfies "clearly identify seed content"
far more reliably than a slug convention someone must remember.

---

## 6. Demo content

A `DemoContentSeeder` creates realistic records covering: a conservation programme, waste
management, marine conservation, biodiversity, climate resilience and community clean-up;
several news articles across categories; sample projects; and sample publications, reports
and policies. All flagged `is_demo = true`.

**Imagery:** a small set of verified CC0 / public-domain Pacific ocean, coastline, forest
and conservation photographs, committed to the repository. Each image's source and licence
is recorded in `docs/LICENSES.md`. No content of any kind is taken from the Fiji
Environment website.

The `is_demo` flag and `demo:purge` command are the safeguard against placeholder
photography surviving into production.

---

## 7. Presentation layer

### 7.1 Design tokens

Tailwind 4 through Vite. The palette is declared once as semantic tokens — `brand`,
`accent`, `eco`, `ink`, `surface` — rather than raw colour names, so a palette revision is
a single-file change.

| Token | Value |
|---|---|
| brand (Pacific Blue) | `#003A70` |
| accent (Niue Yellow) | `#FCD116` |
| eco (Environment Green) | `#287A4B` |
| ink (Deep Navy) | `#142B3A` |
| text | `#253238` |
| surface (Light Background) | `#F5F7F6` |
| white | `#FFFFFF` |

### 7.2 The yellow rule — a hard constraint

Measured contrast ratios:

| Combination | Ratio | Verdict |
|---|---|---|
| Pacific Blue on white | ~11.3:1 | Passes AAA |
| Text `#253238` on `#F5F7F6` | ~12:1 | Passes AAA |
| Environment Green on white | ~5.3:1 | Passes AA |
| **Niue Yellow on white** | **~1.4:1** | **Fails** |
| Deep Navy on Niue Yellow | ~10:1 | Passes AAA |

Therefore Niue Yellow is **never** used as text on white, and never as a button background
with white text. It is used as a background carrying Deep Navy text, as the active-navigation
indicator, as thin accent rules, and as small icon fills.

**Focus rings switch by context:** a blue ring with a white offset on light surfaces, a
yellow ring on dark navy surfaces. Always visible, never invisible against its own
background.

### 7.3 Typography

One self-hosted family — **Public Sans** — bundled through Vite. Not Google Fonts CDN: a
government site should not leak visitor IP addresses to a third party on every page load,
and self-hosting keeps local development offline-capable. Public Sans is openly licensed
and designed for government interfaces. Inter is the accepted alternative if the visual
direction is later revised.

### 7.4 Components

Layout: `<x-layouts.public>` (HTML shell, skip link, dynamic meta), `<x-site.header>`
(government identity bar, primary navigation, mobile menu), `<x-site.footer>`.

Reusable UI: `<x-ui.button>`, `<x-ui.card>`, `<x-ui.section>`.

Content: `<x-content.news-card>`, `<x-content.programme-card>`, `<x-content.document-row>`.

The mobile menu is roughly twenty lines of vanilla JavaScript with correct `aria-expanded`
handling. There is no Alpine, React, Vue, Inertia or any other framework on the public
site.

### 7.5 Homepage

Section order: hero, quick links, programmes, latest news, featured projects, resources,
report-an-issue CTA.

The section order and skeleton are fixed in Blade. Every word, image and featured item
inside them comes from the database: editors change hero headline, intro, background image
and both CTAs; curate the quick links; and flag which Programmes, Projects, News and
Documents appear. Editors cannot break the layout or the accessibility work. Restructuring
the page later is a small development task, not a rebuild.

**No hero carousel.** A static hero image, deliberately — carousels harm Largest Contentful
Paint, are a well-documented accessibility problem, and the brief asks to avoid unnecessary
animation. Editors change the hero image through the CMS instead.

**Empty-state handling.** Each section queries featured items first, falls back to the most
recent, and hides the entire section including its heading when nothing qualifies. A
government homepage showing a bare "Latest News" heading over empty space looks broken.
This is a correctness requirement and is tested.

### 7.6 Accessibility

Landmark regions; exactly one `<h1>` per page; `aria-current` on the active navigation
item; `prefers-reduced-motion` respected; 44px minimum touch targets; visible focus states
per 7.2; proper form labels; semantic HTML throughout.

**Alt text is a required field at upload time in Filament.** This is the only reliable
enforcement point — alt text cannot be retrofitted onto images nobody remembers.

### 7.7 Error handling

Branded 404, 403 and 500 pages under `resources/views/errors/`. A missing featured image
falls back to a deterministic palette-derived placeholder, so cards never render broken.

---

## 8. Security

CSRF protection on all forms; validation through Form Requests; Filament authentication
with rate-limited login; role and policy checks on every admin resource; upload validation
constraining both MIME type and size; all secrets in environment variables with nothing
hard-coded; `/admin` routes protected by authentication and role middleware.

---

## 9. Testing

Pest. Spec 1 covers the failures that would actually cause harm:

1. A record with status Draft, Under Review or Archived never appears in any public output.
2. A record with a future `published_at` never appears in public output.
3. `/admin` rejects unauthenticated visitors; each role sees only what its policy permits.
4. The homepage renders correctly with seeded data.
5. The homepage renders without error when the database is completely empty, with all
   optional sections hidden.

---

## 10. Documentation deliverables

`README.md`, plus `docs/architecture.md`, `docs/cms-guide.md`,
`docs/deployment-readiness.md` and `docs/social-media-integration.md`. Written so another
competent Laravel developer could take the project over. `docs/LICENSES.md` records demo
image provenance.

---

## 11. Explicit exclusions from Spec 1

**Header search is deferred to Spec 3.** Search results are meaningless until the News,
Projects and Resources index pages exist to link to. Shipping a search box that returns
nothing is worse than shipping none, and is an accessibility problem in its own right.
Search arrives with the listing pages.

**Footer link columns render only targets that resolve.** Privacy, Terms and Accessibility
pages are created in Spec 2. Spec 1's footer shows real contact details and working links,
and does not render dead ones.

**Database-driven navigation is deferred to Spec 2**, where it has content to point at.
Spec 1 uses the fixed information architecture from the brief, defined in config.

**All deployment activity is out of scope**, per explicit instruction. The application is
kept deployment-ready; it is not deployed.

---

## 12. Success criteria

Spec 1 is complete when:

1. `https://niue-doe-website.test` serves the homepage from a local PostgreSQL database.
2. Every homepage section's content is editable through `/admin` without touching code.
3. A Super Admin can log in, and Website Manager, Editor and Viewer roles behave per 4.3.
4. All five content types have working Filament resources with the workflow states.
5. Seeded demo content makes the homepage credible for client review, and
   `php artisan demo:purge` removes all of it.
6. The Pest suite passes, including the draft-leakage and empty-database tests.
7. The homepage is responsive and keyboard-navigable, with no contrast failure.

---

## 13. Reference and originality

The Fiji Ministry of Environment website informs layout, information architecture and
general government/environment presentation conventions only. No source code, images, text
or branding is copied from it. The Niue site has its own visual identity built on the
palette above.
