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
    // (options filtering). The security half is covered separately below,
    // via the publish policy ability. Both must hold for the constraint to
    // actually be enforced, not merely suggested by the UI.
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

it('lets an editor update but not delete or publish a project, and lets an admin do both', function () {
    $editor = projectPanelEditor();
    $admin = projectPanelAdmin();
    $project = Project::factory()->create();

    expect($editor->can('update', $project))->toBeTrue()
        ->and($editor->can('delete', $project))->toBeFalse()
        ->and($editor->can('publish', $project))->toBeFalse()
        ->and($admin->can('delete', $project))->toBeTrue()
        ->and($admin->can('publish', $project))->toBeTrue();

    Livewire::actingAs($editor)
        ->test(EditProject::class, ['record' => $project->getRouteKey()])
        ->assertSuccessful();
});
