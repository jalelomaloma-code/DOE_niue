# Design Spec — Niue Department of Environment Website: Pages & Navigation

**Date:** 2026-08-08
**Spec:** 2 of 6
**Status:** Approved
**Author:** Jale Lomaloma / Siale Designs & Multimedia
**Client:** Niue Department of Environment, Government of Niue

---

## 1. Purpose

Spec 1 delivered the foundation and a database-driven homepage. Eleven of the twelve
navigation items still return a branded 404, because the homepage is the only public route
that exists.

This spec builds the narrative pages behind those links, gives Programmes the index and
detail pages their CMS records already deserve, and replaces the hard-coded navigation
with something the Department controls.

The governing constraint remains **handover**: the Department must be able to write, edit
and reorganise these pages themselves, without a developer, and without being able to
break the layout or the accessibility work.

---

## 2. Scope

**In scope:**

- A `Page` content model with a constrained block builder and one level of hierarchy
- The eight pages of the current information architecture: About Us (and its children
  About the Department, Mandate, Mission & Vision, Our Team), Waste & Recycling,
  Biodiversity & Conservation, Climate & Marine, Contact
- `/environment-programmes` as a Page, plus programme detail pages
- A `TeamMember` model rendering on Our Team
- Database-driven navigation replacing `config/navigation.php`
- Privacy, Terms and Accessibility as Pages, unblocking the footer column deferred in
  Spec 1
- Demo content for all of the above

**Out of scope, and where it lives instead:**

| Deferred | Spec |
|---|---|
| News index and article pages, Projects detail, Resources browse/filter, site search, Gallery, Events, Vacancies | 3 |
| Contact enquiry form, Report an Environmental Issue form | 4 |
| Internal dashboard, full SEO, Facebook publishing | 5 |

**Explicitly not built in any current spec:** page templates, content versioning, draft
preview links, scheduled publishing beyond the existing `published_at`, per-page access
control. None appear in the client brief; all are straightforward to add later.

---

## 3. Decisions taken

Four decisions were settled before design, and everything else follows from them.

**Pages use a constrained block builder, not free rich text and not fixed templates.**
Free rich text cannot pull from the document library, and lets editors choose heading
levels — the most common way accessible heading structure degrades. Fixed templates would
mean the Department cannot add a ninth page without a developer, which is the dependency
this architecture exists to remove.

**Programmes get index and detail pages.** The Programme model, its CMS resource and its
homepage cards already exist; without detail pages the Department writes full programme
descriptions that no visitor can read.

**Team members are a model, with photographs.** Staff turnover means one record edited in
one place, and per-person visibility is a checkbox rather than an argument — some staff
will not want a photograph published.

**Navigation stays flat; second-level pages are reached from section landing pages.** The
brief's IA has two levels, but accessible dropdown menus need ARIA, arrow-key navigation,
Escape handling, focus return and a touch story, and they are among the most commonly
botched components on government sites. Landing pages are keyboard-accessible by default,
need no JavaScript, cannot trap focus, and give each section a page worth reading.

---

## 4. Content model

### 4.1 `Page`

`id, title, slug, path, parent_id (nullable, self-referencing), intro (nullable),
content (JSON), sort_order, show_in_section_nav, status, published_at, seo_title,
seo_description, created_by, updated_by, is_demo, timestamps`

Carries `HasStatus`, `HasBlame`, `HasSeo` and `is_demo`, consistent with every other
content model in the project.

`parent_id` provides exactly one level of hierarchy. `path` is unique and indexed, and is
recomputed on save from the page's slug and its ancestry — so a page's URL resolves in a
single indexed lookup rather than by walking segments. When a parent's slug changes, its
descendants' paths are recomputed.

**`intro` is the page's lede** — one or two sentences rendered under the title, and reused
as the summary when the page is listed on its section landing page. It is a plain-text
column, not a block, precisely so listings have something predictable to show without
parsing block JSON.

**`show_in_section_nav` does not control the header.** The header is driven entirely by
`NavigationItem` (§4.4). This flag controls whether a *child* page appears in its section
landing page's list of children and in the sibling navigation on related pages. Privacy,
Terms and Accessibility are top-level pages with this flag off and no `NavigationItem` —
reachable from the footer only, which is where a government site puts them.

### 4.2 The block set

Seven blocks. The set is deliberately small: adding an eighth later is cheap, removing one
that editors have already used is not.

| Block | Purpose |
|---|---|
| Rich text | Prose. Sanitised on save (see §6). |
| Image with caption | A single image, alt text required |
| Callout | A highlighted note or warning |
| Card grid | Manually entered cards — title, text, optional link |
| Documents list | Pulls live from the document library, filtered by category |
| Programmes list | Pulls live published programmes |
| Contact details | Renders from `SiteSetting`, so the phone number is never stale in three places |

The set was tested against the current IA: Waste & Recycling needs intro prose, service
cards and the relevant forms — covered by rich text, card grid and documents list.

### 4.3 `TeamMember`

`id, name, role, photo (media), bio (nullable), email (nullable), sort_order, is_active,
is_demo, timestamps`

Not a content model: no workflow states, no slug, no SEO fields. `is_active` handles
someone leaving without destroying the record. Alt text on the photograph is required, as
everywhere else in this project.

### 4.4 `NavigationItem`

`id, label, url, sort_order, is_active, timestamps`

Deliberately the same shape as `QuickLink`, including its hardened URL validation, because
navigation must point at both Pages and the model-driven routes arriving in Spec 3
(`/news`, `/resources`, `/gallery`). Copying that proven shape rather than abstracting it
is consistent with how the four existing content models relate to one another.

No `is_demo` column: like `QuickLink`, these are real content and must survive
`demo:purge`.

---

## 5. Routing

**Explicit routes register first:**

- `/` (exists)
- `/environment-programmes/{programme:slug}` — programme detail

**The `Page` catch-all registers last**, with `/admin`, `/storage` and `/livewire`
excluded by pattern.

A catch-all is necessary: without it the Department cannot add a ninth page without a
developer, which would undo the block-builder decision. The risk it carries is managed in
two places, not one:

1. **Route order and prefix exclusion**, so the panel and storage are never shadowed.
2. **Reserved slugs rejected at CMS save time.** An editor typing `admin` as a slug gets a
   validation error, rather than silently shadowing — or being shadowed by — the panel.
   Catching it at creation is the difference between a clear message and an unexplainable
   404 six months later.

`/environment-programmes` is an ordinary Page whose content includes the programmes-list
block. No special-cased controller, and no coupling between a route and a particular Page
record.

Unpublished and future-dated pages return 404 to the public, through the same
`published()` scope everything else uses. There is no preview mode in this spec.

Contact is a Page rendering the contact-details block. The enquiry form is Spec 4, so this
page has real content and no form — a dead form is not rendered.

---

## 6. Rendering and safety

**Block rendering.** One Blade partial per block under
`resources/views/components/blocks/`, dispatched by a single `<x-page.content>` component.
Each partial is independently understandable and testable; an eighth block means one
schema plus one partial, with no central file growing over time.

**Unknown blocks degrade, never fatal.** If a block type is removed from the code while
pages still hold it in their JSON, the renderer skips it and logs a warning. A page
missing one section is recoverable; a 500 on a live government page is not.

**Heading hierarchy is enforced, not requested.** The page title is the only `<h1>`; block
headings are always `<h2>`; content within a block is `<h3>`. Editors choose words, never
levels.

**Rich text is sanitised on save.** This is the one real security decision in the spec.
Filament's editor stores HTML, and rendering it requires unescaped output. An editor
pasting from Word or webmail can carry in `<script>`, event handlers, or a `javascript:`
href. Spec 1 already found one stored-XSS path through a CMS-editable field, so this is
treated as certain rather than theoretical: HTML is sanitised against a tag and attribute
allowlist **when saved**, so the database never holds anything dangerous. Sanitising on
render would leave the payload in storage and depend on every future render path
remembering to clean it.

The sanitiser must be **allowlist-based**, not a blocklist — blocklists lose to novel
payloads. The allowlist covers the tags Filament's editor can produce (headings,
paragraphs, lists, bold, italic, links, blockquote) and strips every attribute except
`href` on links, with `href` itself restricted to `http`, `https`, `mailto` and
site-relative paths — the same scheme restriction already applied to `QuickLink` and
`NavigationItem` URLs. Choosing the specific library is an implementation decision for the
plan; any dependency added must be verified against Laravel 13 on Packagist first, as
every dependency in this project has been.

---

## 7. Navigation and site chrome

The header reads `NavigationItem::active()`. `config/navigation.php` is deleted.

Section landing pages list their published children. Child pages carry a small sibling
navigation. The footer's link columns become database-driven, and the Privacy / Terms /
Accessibility column deferred in Spec 1 renders once those Pages exist.

The header's existing structure — two nav lists inside one `<nav aria-label="Primary">`,
a desktop `hidden lg:flex` list and a mobile native `<details>` disclosure — is preserved.
That structure exists because Chromium hides closed-`<details>` content through shadow-DOM
slot assignment rather than `display`, so no CSS override can force one open at a
breakpoint. Only the data source changes.

---

## 8. Testing

- Every block renders with representative content
- An unknown block type degrades without fataling
- Unpublished and future-dated pages 404 for the public
- A reserved slug is rejected at save time
- Renaming a parent recomputes its descendants' paths
- Navigation renders from the database
- Sanitisation strips a `<script>` tag and a `javascript:` href on save
- A page with no blocks renders without error
- Programme detail 404s for an unpublished programme

---

## 9. Demo content

The eight IA pages, the Privacy / Terms / Accessibility pages, and a few team members —
all flagged `is_demo` and removed by the existing `demo:purge`. Team photographs use the
generated placeholder graphics established in Spec 1.

Demo copy must not name real Niue institutions, statistics, dates or events. Spec 1 found
fabricated narrative attached to the actual name of Niue's marine protected area; the same
discipline applies here, and applies to invented staff names and roles in particular —
demo team members must be unmistakably fictional.

---

## 10. Success criteria

1. Every navigation item resolves to a real page; no 404 from the primary navigation.
2. The Department can create, edit, reorder and unpublish a page through the CMS, and can
   add a new page that appears in navigation, without a developer.
3. Programme cards on the homepage lead to programme detail pages.
4. Heading hierarchy is correct on every page regardless of what editors typed.
5. A `<script>` tag pasted into rich text is not present in the database after save.
6. The full test suite passes, including Spec 1's 118 tests.
