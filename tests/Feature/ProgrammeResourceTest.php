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
    // (options filtering). The security half is covered separately below,
    // via the publish policy ability. Both must hold for the constraint to
    // actually be enforced, not merely suggested by the UI.
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

it('lets an editor update but not delete or publish a programme, and lets an admin do both', function () {
    $editor = programmePanelEditor();
    $admin = programmePanelAdmin();
    $programme = Programme::factory()->create();

    expect($editor->can('update', $programme))->toBeTrue()
        ->and($editor->can('delete', $programme))->toBeFalse()
        ->and($editor->can('publish', $programme))->toBeFalse()
        ->and($admin->can('delete', $programme))->toBeTrue()
        ->and($admin->can('publish', $programme))->toBeTrue();

    Livewire::actingAs($editor)
        ->test(EditProgramme::class, ['record' => $programme->getRouteKey()])
        ->assertSuccessful();
});
