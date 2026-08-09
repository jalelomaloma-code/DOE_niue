<?php

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
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
