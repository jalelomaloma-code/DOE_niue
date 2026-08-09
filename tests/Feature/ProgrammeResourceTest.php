<?php

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Programmes\Pages\CreateProgramme;
use App\Filament\Resources\Programmes\Pages\EditProgramme;
use App\Filament\Resources\Programmes\Pages\ListProgrammes;
use App\Models\Programme;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

function programmePanelAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);

    return $user;
}

function programmePanelEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::Editor->value);

    return $user;
}

it('lists programmes in the panel', function () {
    $admin = programmePanelAdmin();
    Programme::factory()->count(2)->create();

    Livewire::actingAs($admin)
        ->test(ListProgrammes::class)
        ->assertSuccessful();
});

it('creates a programme with no image and no alt text required', function () {
    $admin = programmePanelAdmin();

    Livewire::actingAs($admin)
        ->test(CreateProgramme::class)
        ->fillForm([
            'title' => 'No Image Programme',
            'slug' => 'no-image-programme',
            'summary' => 'A programme with no featured image.',
            'status' => ContentStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Programme::where('slug', 'no-image-programme')->exists())->toBeTrue();
});

it('requires alt text once an image is attached, and saves the alt as a media custom property', function () {
    Storage::fake('public');
    $admin = programmePanelAdmin();

    // Image uploaded, alt text left blank -> validation error. This is the
    // only reliable enforcement point for alt text (see CLAUDE.md-style
    // global constraint: it cannot be retrofitted onto images nobody
    // remembers), so this test is the sole regression guard for it.
    Livewire::actingAs($admin)
        ->test(CreateProgramme::class)
        ->fillForm([
            'title' => 'Marine Conservation Programme',
            'slug' => 'marine-conservation-programme',
            'summary' => 'Protecting the reef.',
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['featured_image_alt' => 'required']);

    expect(Programme::where('slug', 'marine-conservation-programme')->exists())->toBeFalse();

    // Image uploaded with alt text -> succeeds, and the alt is stored on the media.
    Livewire::actingAs($admin)
        ->test(CreateProgramme::class)
        ->fillForm([
            'title' => 'Marine Conservation Programme',
            'slug' => 'marine-conservation-programme',
            'summary' => 'Protecting the reef.',
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => 'Coral reef off the coast of Niue',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $programme = Programme::where('slug', 'marine-conservation-programme')->firstOrFail();

    expect($programme->featuredImageAlt())->toBe('Coral reef off the coast of Niue');
});

it('hides the Published option from Editors but keeps it enabled for admins', function () {
    // Two halves of "Editors cannot publish": this is the usability half
    // (options filtering). The enforcement half is the test below, which
    // posts `published` at the server instead of reading the option list.
    $editor = programmePanelEditor();
    $admin = programmePanelAdmin();

    Livewire::actingAs($editor)
        ->test(CreateProgramme::class)
        ->assertFormFieldExists('status', function ($field) {
            /** @var Select $field */
            return ! array_key_exists(ContentStatus::Published->value, $field->getOptions());
        });

    Livewire::actingAs($admin)
        ->test(CreateProgramme::class)
        ->assertFormFieldExists('status', function ($field) {
            /** @var Select $field */
            return array_key_exists(ContentStatus::Published->value, $field->getOptions());
        });
});

it('lets an admin edit and re-save a programme that already has an image, without re-entering alt text', function () {
    // Regression guard: SpatieMediaLibraryFileUpload::loadStateFromRelationshipsUsing
    // (vendor/filament/spatie-laravel-media-library-plugin/.../SpatieMediaLibraryFileUpload.php:56-72)
    // populates 'featured_image' with the existing media's uuid on every Edit
    // page load, which made featured_image_alt's `required(fn ($get) =>
    // filled($get('featured_image')))` condition true from the moment the
    // page opened -- before anyone touched the form -- while the alt field
    // itself rendered blank because nothing hydrated it from the record.
    Storage::fake('public');
    $admin = programmePanelAdmin();

    Livewire::actingAs($admin)
        ->test(CreateProgramme::class)
        ->fillForm([
            'title' => 'Reef Restoration Programme',
            'slug' => 'reef-restoration-programme',
            'summary' => 'Restoring damaged reef sections.',
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => 'Divers replanting coral fragments',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $programme = Programme::where('slug', 'reef-restoration-programme')->firstOrFail();

    // Edit the record and change only the title -- do not touch alt text.
    Livewire::actingAs($admin)
        ->test(EditProgramme::class, ['record' => $programme->getRouteKey()])
        ->fillForm([
            'title' => 'Reef Restoration Programme (Updated)',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($programme->fresh()->title)->toBe('Reef Restoration Programme (Updated)')
        ->and($programme->fresh()->featuredImageAlt())->toBe('Divers replanting coral fragments');
});

it('lets an editor update but not delete a programme, and lets an admin delete', function () {
    $editor = programmePanelEditor();
    $admin = programmePanelAdmin();
    $programme = Programme::factory()->create();

    expect($editor->can('update', $programme))->toBeTrue()
        ->and($editor->can('delete', $programme))->toBeFalse()
        ->and($admin->can('delete', $programme))->toBeTrue();

    Livewire::actingAs($editor)
        ->test(EditProgramme::class, ['record' => $programme->getRouteKey()])
        ->assertSuccessful();
});

// Replaces a pair of `can('publish', $programme)` assertions that exercised
// ProgrammePolicy::publish() -- an ability nothing in production ever called,
// and which has since been deleted. Those assertions would have kept passing
// with the real control removed entirely.
//
// The real control is the status Select's ->options() closure in
// ProgrammeForm: Filament derives a server-side `in:` rule from whichever
// options it returns, re-evaluated per request against the acting user, so an
// Editor posting `published` fails Laravel's own validation. Posting the
// payload is the only way to see that; reading the option list (the test
// above) only proves the UI never offers it.
//
// The admin arm is load-bearing. Without it this test still passes if the
// closure returns no options at all, or if the form is broken outright.
it('rejects a published status from an editor at the server, but allows it from an admin', function () {
    Livewire::actingAs(programmePanelEditor())
        ->test(CreateProgramme::class)
        ->fillForm([
            'title' => 'Editor Published Attempt',
            'slug' => 'editor-published-attempt',
            'summary' => 'A programme an editor should not be able to publish.',
            'status' => ContentStatus::Published->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['status']);

    expect(Programme::where('slug', 'editor-published-attempt')->exists())->toBeFalse();

    Livewire::actingAs(programmePanelAdmin())
        ->test(CreateProgramme::class)
        ->fillForm([
            'title' => 'Admin Published Programme',
            'slug' => 'admin-published-programme',
            'summary' => 'A programme an admin may publish.',
            'status' => ContentStatus::Published->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Programme::where('slug', 'admin-published-programme')->first()?->status)
        ->toBe(ContentStatus::Published);
});
