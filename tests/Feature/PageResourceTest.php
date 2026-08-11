<?php

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Route;
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

it('strips a script tag from rich text before it reaches the database', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Waste',
            'slug' => 'waste',
            'content' => [
                // <h1> is included alongside <script> deliberately: Tiptap's
                // own HTML parser has no `script` node in its schema, so a
                // <script> tag is neutralised the moment the RichEditor's
                // state cast parses the pasted HTML into a Tiptap document --
                // BEFORE ->dehydrateStateUsing() (RichTextSanitiser) ever
                // runs. Asserting on the script tag alone cannot fail even
                // if the sanitiser call is deleted from PageForm; it was
                // verified to still pass with ->dehydrateStateUsing() removed
                // entirely. Tiptap's schema *does* support arbitrary heading
                // levels, so a pasted <h1> survives Tiptap intact -- only
                // RichTextSanitiser's blockElement('h1') (fired through
                // dehydrateStateUsing) unwraps it. The <h1> assertion below
                // is what actually proves dehydrateStateUsing is wired;
                // removing it was confirmed to fail this test.
                ['type' => 'rich_text', 'data' => ['body' => '<h1>Big Heading</h1><p>Safe</p><script>alert(1)</script>']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $stored = json_encode(Page::where('slug', 'waste')->first()->content);

    expect($stored)->not->toContain('script')
        ->and($stored)->not->toContain('<h1')
        ->and($stored)->toContain('Safe')
        ->and($stored)->toContain('Big Heading');
});

// Page::guardAgainstCyclicParent() throws a raw InvalidArgumentException from
// the `saving` model event (see PageTest.php for that half). Filament does
// not translate arbitrary domain exceptions into inline field errors, so
// without a form-level rule an admin picking a cyclic parent would hit an
// error page instead of a validation message. These two tests cover the
// usability half: the Select's own ->rule() closure on parent_id.
it('rejects a page being set as its own parent through the form, with a field error', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $page = Page::factory()->create(['slug' => 'self-parent', 'parent_id' => null]);

    Livewire::test(EditPage::class, ['record' => $page->getKey()])
        ->fillForm(['parent_id' => $page->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);

    expect($page->fresh()->parent_id)->toBeNull();
});

it('rejects a transitive cyclic parent through the form, with a field error', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $a = Page::factory()->create(['slug' => 'cycle-a', 'parent_id' => null]);
    $b = Page::factory()->create(['slug' => 'cycle-b', 'parent_id' => $a->id]);
    $c = Page::factory()->create(['slug' => 'cycle-c', 'parent_id' => $b->id]);

    // A -> B -> C already. Making C the parent of A would close the loop.
    Livewire::test(EditPage::class, ['record' => $a->getKey()])
        ->fillForm(['parent_id' => $c->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);

    expect($a->fresh()->parent_id)->toBeNull();
});

it('lets a page keep an ordinary, non-cyclic parent through the form', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $parent = Page::factory()->create(['slug' => 'ordinary-parent', 'parent_id' => null]);
    $child = Page::factory()->create(['slug' => 'ordinary-child', 'parent_id' => null]);

    Livewire::test(EditPage::class, ['record' => $child->getKey()])
        ->fillForm(['parent_id' => $parent->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($child->fresh()->parent_id)->toBe($parent->id);
});

// The publish enforcement is the status Select's ->options() closure in
// PageForm, which derives a server-side `in:` validation rule from whichever
// options it returns. An Editor never gets `published` in that list, so
// submitting it anyway fails Laravel's own `in:` rule, not a custom check.
// Posting the payload directly (rather than only checking the option list, as
// the "hides the published option" test above does) proves the server-side
// rule rejects it too -- not just that the UI never offers it. The Website
// Manager half is what stops this test from passing for the wrong reason: a
// rule that rejected `published` unconditionally, for every role, would
// satisfy the Editor half alone.
//
// This replaced a `can('publish', $page)` pair that exercised
// PagePolicy::publish(), an ability nothing in production called; the policy
// method has since been deleted, and the same shape of test now exists on
// Documents, News, Programmes and Projects.
it('rejects a published status from an editor at the server, but allows it from a website manager', function () {
    $this->actingAs(pageUser(UserRole::Editor));

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Editor Published Attempt',
            'slug' => 'editor-published-attempt',
            'status' => 'published',
        ])
        ->call('create')
        ->assertHasFormErrors(['status']);

    expect(Page::where('slug', 'editor-published-attempt')->exists())->toBeFalse();

    $this->actingAs(pageUser(UserRole::WebsiteManager));

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Manager Published Page',
            'slug' => 'manager-published-page',
            'status' => 'published',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::where('slug', 'manager-published-page')->first()?->status)
        ->toBe(ContentStatus::Published);
});

/*
 * ---------------------------------------------------------------------------
 * Reordering. Spec 2 success criterion 2 requires the Department to be able to
 * REORDER a page through the CMS without a developer. `sort_order` is the
 * column Page::children() orders by, and PageController feeds that relation to
 * the "In this section" list, so these tests drive the CMS form and then read
 * the rendered section landing page. Asserting the field exists on the form
 * would prove nothing -- the field has to actually move the page on the site.
 * ---------------------------------------------------------------------------
 */

it('reorders a section listing from the CMS, and the site reflects the new order', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $section = Page::factory()->create(['title' => 'Our Work', 'slug' => 'our-work', 'parent_id' => null]);
    $waste = Page::factory()->create([
        'title' => 'Waste And Recycling', 'slug' => 'waste-and-recycling',
        'parent_id' => $section->id, 'sort_order' => 0,
    ]);
    $climate = Page::factory()->create([
        'title' => 'Climate Resilience', 'slug' => 'climate-resilience',
        'parent_id' => $section->id, 'sort_order' => 1,
    ]);

    // Baseline, so the assertion below is a change and not a coincidence.
    $before = $this->withoutVite()->get('/our-work')->assertOk()->getContent();
    expect(strpos($before, 'Waste And Recycling'))->toBeLessThan(strpos($before, 'Climate Resilience'));

    // The only route the Department has: the page's own edit form.
    Livewire::test(EditPage::class, ['record' => $waste->getKey()])
        ->fillForm(['sort_order' => 5])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($waste->fresh()->sort_order)->toBe(5);

    $after = $this->withoutVite()->get('/our-work')->assertOk()->getContent();
    expect(strpos($after, 'Climate Resilience'))->toBeLessThan(strpos($after, 'Waste And Recycling'));
});

it('sets a new page\'s position at creation time, not only afterwards', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $section = Page::factory()->create(['title' => 'Our Work', 'slug' => 'our-work', 'parent_id' => null]);
    Page::factory()->create([
        'title' => 'Waste And Recycling', 'slug' => 'waste-and-recycling',
        'parent_id' => $section->id, 'sort_order' => 5,
    ]);

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Climate Resilience',
            'slug' => 'climate-resilience',
            'parent_id' => $section->id,
            'sort_order' => 1,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::where('slug', 'climate-resilience')->firstOrFail()->sort_order)->toBe(1);

    $html = $this->withoutVite()->get('/our-work')->assertOk()->getContent();
    expect(strpos($html, 'Climate Resilience'))->toBeLessThan(strpos($html, 'Waste And Recycling'));
});

it('rejects a non-numeric position rather than silently storing zero', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Ordered', 'slug' => 'ordered', 'sort_order' => 'first'])
        ->call('create')
        ->assertHasFormErrors(['sort_order']);

    expect(Page::where('slug', 'ordered')->exists())->toBeFalse();
});

/*
 * ---------------------------------------------------------------------------
 * CMS-write vs router-read agreement.
 *
 * Every test below exists because the failure it prevents is invisible: the
 * CMS lists the page as Published, the site returns 404, and nothing is
 * logged. The reserved-slug rule alone did not close these -- it matched
 * Page::RESERVED_SLUGS exactly, while the catch-all's lookahead is
 * prefix-based and its charset is narrower than "any string".
 * ---------------------------------------------------------------------------
 */

/*
 * `up` is the fourth instance of this defect. bootstrap/app.php passes
 * health: '/up' to withRouting(), and Laravel registers that route ahead of
 * routes/web.php -- so it shadows the catch-all, but it appears in no route
 * file, which is why three passes over routes/web.php missed it.
 */
it('rejects a slug that collides with Laravel\'s health endpoint, and proves the router could not have served it', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Up', 'slug' => 'up'])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    expect(Page::where('slug', 'up')->exists())->toBeFalse();

    // The other half: seed the row directly and confirm /up really is the
    // health endpoint and not the page. Asserting a 404 would be wrong here --
    // the route exists and returns 200 -- so assert on the body instead.
    $seeded = Page::factory()->create([
        'title' => 'Seeded Up Page',
        'slug' => 'up',
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $this->withoutVite()->get('/'.$seeded->path)->assertDontSee('Seeded Up Page');
});

/*
 * The generalisation, derived from the router rather than maintained by hand.
 * Three rounds of review each found one more route the constants did not know
 * about; this fails the moment a fourth appears, without anyone having to
 * remember. It reads the real route table, so a route registered by the
 * framework or by a package (as /up is) counts exactly the same as one written
 * in routes/web.php.
 */
it('reserves every path a route registered before the page catch-all would shadow', function () {
    foreach (Route::getRoutes() as $route) {
        if ($route->getName() === 'pages.show') {
            continue; // The catch-all itself.
        }

        // Substitute a plausible slug for each parameter: the question is
        // whether this route can occupy a path shape the catch-all also serves,
        // and `storage/{path}` occupies `storage/anything` just as surely as a
        // literal would.
        $segments = explode('/', preg_replace('/\{[^}]+\}/', 'x', $route->uri()));

        // Deeper than the catch-all serves, so no Page can have this path.
        if (count($segments) > Page::MAX_DEPTH) {
            continue;
        }

        // Outside the slug charset (e.g. `livewire-.../livewire.js`), so the
        // catch-all's `where` constraint would refuse it anyway.
        foreach ($segments as $segment) {
            if (! preg_match('/^'.Page::SLUG_PATTERN.'$/', $segment)) {
                continue 2;
            }
        }

        // A Page could legitimately be given this path, and this route would
        // win. The CMS has to refuse it.
        $slug = $segments[0];
        $path = implode('/', $segments);
        $reserved = in_array($slug, Page::RESERVED_SLUGS, true)
            || Page::pathIsReserved($path);

        foreach (Page::ROUTE_EXCLUDED_PREFIXES as $prefix) {
            $reserved = $reserved || str_starts_with($slug, $prefix);
        }

        expect($reserved)->toBeTrue(
            "The route \"{$route->uri()}\" is registered before the page catch-all and would "
            ."shadow a page at \"{$path}\". Add it to Page::RESERVED_PATHS, "
            .'Page::RESERVED_SLUGS or Page::ROUTE_EXCLUDED_PREFIXES, or the page will save cleanly, list as '
            .'Published, and never be reachable.'
        );
    }
});

it('rejects exact public route paths while allowing the same child slug elsewhere', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $ourWork = Page::factory()->create([
        'title' => 'Our Work',
        'slug' => 'our-work',
        'parent_id' => null,
    ]);
    $about = Page::factory()->create([
        'title' => 'About Us',
        'slug' => 'about-us',
        'parent_id' => null,
    ]);

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Projects',
            'slug' => 'projects',
            'parent_id' => $ourWork->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Projects',
            'slug' => 'projects',
            'parent_id' => $about->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::where('path', 'our-work/projects')->exists())->toBeFalse()
        ->and(Page::where('path', 'about-us/projects')->exists())->toBeTrue();
});

it('rejects a slug that merely starts with a route-excluded prefix, and proves the router could not have served it', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    foreach (Page::ROUTE_EXCLUDED_PREFIXES as $prefix) {
        // "administration" is an entirely plausible About-Us child for a
        // government department, and it starts with "admin".
        $slug = $prefix.'istration-office';

        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Prefixed '.$prefix, 'slug' => $slug])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        expect(Page::where('slug', $slug)->exists())->toBeFalse();

        // The other half, and the reason this test cannot drift: seed the row
        // directly, bypassing the form, and confirm the router really does
        // refuse it. If someone relaxes the form rule without relaxing the
        // route (or the reverse), one of these two halves fails.
        //
        // status is set explicitly. assertNotFound() has to mean "the ROUTE
        // refused this path"; if the page were a draft it would 404 for an
        // entirely different reason (PageController's published scope) and this
        // half would pass while proving nothing. PageFactory happens to default
        // to Published today, but relying on that makes the assertion's meaning
        // depend on a factory default nothing here declares.
        $seeded = Page::factory()->create([
            'title' => 'Seeded '.$prefix,
            'slug' => $slug,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $this->withoutVite()->get('/'.$seeded->path)->assertNotFound();
    }
});

/*
 * The prefix rule above must be anchored the way the ROUTE is anchored. The
 * catch-all's negative lookahead sits at the start of the whole path, not at
 * the start of each segment, so `/about/administration` matches it perfectly
 * well -- only a TOP-LEVEL slug beginning with a reserved prefix is
 * unreachable. Applying the rule to child slugs too rejected a legitimate
 * About-Us child with a message ("The website could not open this page") that
 * was simply untrue of that page.
 */
it('accepts a child page whose slug starts with a route-excluded prefix, and serves it', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $parent = Page::factory()->create([
        'title' => 'About Us',
        'slug' => 'about',
        'parent_id' => null,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Administration',
            'slug' => 'administration',
            'parent_id' => $parent->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $child = Page::where('slug', 'administration')->firstOrFail();

    // The half that proves the form was right to accept it: the real router
    // serves the real path. Without this, relaxing the rule too far would look
    // like a pass.
    expect($child->path)->toBe('about/administration');
    $this->withoutVite()->get('/'.$child->path)->assertOk()->assertSee('Administration');
});

it('rejects a slug with characters the route cannot match', function ($slug) {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Charset Probe', 'slug' => $slug])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    expect(Page::where('slug', $slug)->exists())->toBeFalse();
})->with([
    'uppercase' => 'Our-Work',
    'underscore' => 'about_us',
    'space' => 'about us',
    'slash' => 'about/us',
    'accent' => 'niuē',
]);

it('rejects a third level of nesting: a parent that already has a parent', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $section = Page::factory()->create(['slug' => 'our-work', 'parent_id' => null]);
    $child = Page::factory()->create(['slug' => 'environment-programmes', 'parent_id' => $section->id]);

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Coral Watch', 'slug' => 'coral-watch', 'parent_id' => $child->id])
        ->call('create')
        ->assertHasFormErrors(['parent_id']);

    expect(Page::where('slug', 'coral-watch')->exists())->toBeFalse();
});

/*
 * The review prescribed only the "parent already has a parent" direction.
 * Depth is reachable from the other end too: give a parent to a page that
 * already has children of its own and those children land three segments
 * deep, with no warning and no error, because the cascade in Page::booted()
 * silently recomputes their paths.
 */
it('rejects giving a parent to a page that already has children of its own', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $topLevel = Page::factory()->create(['slug' => 'our-work', 'parent_id' => null]);
    Page::factory()->create(['slug' => 'environment-programmes', 'parent_id' => $topLevel->id]);

    $newSection = Page::factory()->create(['slug' => 'about', 'parent_id' => null]);

    Livewire::test(EditPage::class, ['record' => $topLevel->getKey()])
        ->fillForm(['parent_id' => $newSection->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);

    expect($topLevel->fresh()->parent_id)->toBeNull();
});

/*
 * The part that actually proves the two ends agree. A validation test that
 * never touches the router would drift again, which is how this bug got in.
 * Everything here goes in through the real Filament form and comes back out
 * through the real route.
 */
it('serves every slug shape the CMS accepts, through the real router', function () {
    $this->actingAs(pageUser(UserRole::WebsiteManager));

    $accepted = ['about', 'our-work', 'waste-and-recycling', 'a', 'plan-2030', '2030-review'];

    foreach ($accepted as $slug) {
        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Accepted '.$slug,
                'slug' => $slug,
                'status' => 'published',
                'published_at' => now()->subDay(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::where('slug', $slug)->firstOrFail();

        $this->withoutVite()->get('/'.$page->path)->assertOk();
    }

    // And the two-level case the catch-all's optional second segment exists
    // for -- created through the form, parent and all.
    $parent = Page::where('slug', 'about')->firstOrFail();

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Our Mandate',
            'slug' => 'mandate',
            'parent_id' => $parent->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $child = Page::where('slug', 'mandate')->firstOrFail();

    expect($child->path)->toBe('about/mandate');
    $this->withoutVite()->get('/'.$child->path)->assertOk();
});
