<?php

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
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

function projectPanelAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);

    return $user;
}

function projectPanelEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::Editor->value);

    return $user;
}

it('lists projects in the panel', function () {
    $admin = projectPanelAdmin();
    Project::factory()->count(2)->create();

    Livewire::actingAs($admin)
        ->test(ListProjects::class)
        ->assertSuccessful();
});

it('creates a project with no image and no alt text required', function () {
    $admin = projectPanelAdmin();

    Livewire::actingAs($admin)
        ->test(CreateProject::class)
        ->fillForm([
            'title' => 'No Image Project',
            'slug' => 'no-image-project',
            'summary' => 'A project with no featured image.',
            'status' => ContentStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Project::where('slug', 'no-image-project')->exists())->toBeTrue();
});

it('requires alt text once an image is attached, and saves the alt as a media custom property', function () {
    Storage::fake('public');
    $admin = projectPanelAdmin();

    // Image uploaded, alt text left blank -> validation error. This is the
    // only reliable enforcement point for alt text, so this test is the sole
    // regression guard for it.
    Livewire::actingAs($admin)
        ->test(CreateProject::class)
        ->fillForm([
            'title' => 'Reef Rehabilitation Project',
            'slug' => 'reef-rehabilitation-project',
            'summary' => 'Restoring the reef.',
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['featured_image_alt' => 'required']);

    expect(Project::where('slug', 'reef-rehabilitation-project')->exists())->toBeFalse();

    // Image uploaded with alt text -> succeeds, and the alt is stored on the media.
    Livewire::actingAs($admin)
        ->test(CreateProject::class)
        ->fillForm([
            'title' => 'Reef Rehabilitation Project',
            'slug' => 'reef-rehabilitation-project',
            'summary' => 'Restoring the reef.',
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => 'Coral reef off the coast of Niue',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::where('slug', 'reef-rehabilitation-project')->firstOrFail();

    expect($project->featuredImageAlt())->toBe('Coral reef off the coast of Niue');
});

it('hides the Published option from Editors but keeps it enabled for admins', function () {
    // Two halves of "Editors cannot publish": this is the usability half
    // (options filtering). The enforcement half is the test below, which
    // posts `published` at the server instead of reading the option list.
    $editor = projectPanelEditor();
    $admin = projectPanelAdmin();

    Livewire::actingAs($editor)
        ->test(CreateProject::class)
        ->assertFormFieldExists('status', function ($field) {
            /** @var Select $field */
            return ! array_key_exists(ContentStatus::Published->value, $field->getOptions());
        });

    Livewire::actingAs($admin)
        ->test(CreateProject::class)
        ->assertFormFieldExists('status', function ($field) {
            /** @var Select $field */
            return array_key_exists(ContentStatus::Published->value, $field->getOptions());
        });
});

it('lets an admin edit and re-save a project that already has an image, without re-entering alt text', function () {
    // Regression guard: SpatieMediaLibraryFileUpload::loadStateFromRelationshipsUsing
    // populates 'featured_image' with the existing media's uuid on every Edit
    // page load, which made featured_image_alt's `required(fn ($get) =>
    // filled($get('featured_image')))` condition true from the moment the
    // page opened -- before anyone touched the form -- while the alt field
    // itself rendered blank because nothing hydrated it from the record.
    Storage::fake('public');
    $admin = projectPanelAdmin();

    Livewire::actingAs($admin)
        ->test(CreateProject::class)
        ->fillForm([
            'title' => 'Marine Debris Cleanup Project',
            'slug' => 'marine-debris-cleanup-project',
            'summary' => 'Removing debris from the coastline.',
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => 'Volunteers collecting marine debris',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::where('slug', 'marine-debris-cleanup-project')->firstOrFail();

    // Edit the record and change only the title -- do not touch alt text.
    Livewire::actingAs($admin)
        ->test(EditProject::class, ['record' => $project->getRouteKey()])
        ->fillForm([
            'title' => 'Marine Debris Cleanup Project (Updated)',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($project->fresh()->title)->toBe('Marine Debris Cleanup Project (Updated)')
        ->and($project->fresh()->featuredImageAlt())->toBe('Volunteers collecting marine debris');
});

it('lets an editor update but not delete a project, and lets an admin delete', function () {
    $editor = projectPanelEditor();
    $admin = projectPanelAdmin();
    $project = Project::factory()->create();

    expect($editor->can('update', $project))->toBeTrue()
        ->and($editor->can('delete', $project))->toBeFalse()
        ->and($admin->can('delete', $project))->toBeTrue();

    Livewire::actingAs($editor)
        ->test(EditProject::class, ['record' => $project->getRouteKey()])
        ->assertSuccessful();
});

// Replaces a pair of `can('publish', $project)` assertions that exercised
// ProjectPolicy::publish() -- an ability nothing in production ever called,
// and which has since been deleted. Those assertions would have kept passing
// with the real control removed entirely.
//
// The real control is the status Select's ->options() closure in ProjectForm:
// Filament derives a server-side `in:` rule from whichever options it returns,
// re-evaluated per request against the acting user, so an Editor posting
// `published` fails Laravel's own validation. Posting the payload is the only
// way to see that; reading the option list (the test above) only proves the UI
// never offers it.
//
// The admin arm is load-bearing. Without it this test still passes if the
// closure returns no options at all, or if the form is broken outright.
it('rejects a published status from an editor at the server, but allows it from an admin', function () {
    Livewire::actingAs(projectPanelEditor())
        ->test(CreateProject::class)
        ->fillForm([
            'title' => 'Editor Published Attempt',
            'slug' => 'editor-published-attempt',
            'summary' => 'A project an editor should not be able to publish.',
            'status' => ContentStatus::Published->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['status']);

    expect(Project::where('slug', 'editor-published-attempt')->exists())->toBeFalse();

    Livewire::actingAs(projectPanelAdmin())
        ->test(CreateProject::class)
        ->fillForm([
            'title' => 'Admin Published Project',
            'slug' => 'admin-published-project',
            'summary' => 'A project an admin may publish.',
            'status' => ContentStatus::Published->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Project::where('slug', 'admin-published-project')->first()?->status)
        ->toBe(ContentStatus::Published);
});
