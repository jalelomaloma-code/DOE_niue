<?php

use App\Enums\UserRole;
use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Models\TeamMember;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function teamMemberPanelUser(UserRole $role): User
{
    Role::findOrCreate($role->value);

    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('blocks a viewer from creating or updating a team member, but lets an editor update, at the Gate level and the HTTP level', function () {
    $viewer = teamMemberPanelUser(UserRole::Viewer);
    $editor = teamMemberPanelUser(UserRole::Editor);
    $member = TeamMember::factory()->create();

    expect($viewer->can('create', TeamMember::class))->toBeFalse()
        ->and($viewer->can('update', $member))->toBeFalse()
        ->and($editor->can('create', TeamMember::class))->toBeTrue()
        ->and($editor->can('update', $member))->toBeTrue();

    $this->actingAs($viewer)
        ->get(CreateTeamMember::getUrl())
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(EditTeamMember::getUrl(['record' => $member->getKey()]))
        ->assertForbidden();

    $this->actingAs($editor)
        ->get(CreateTeamMember::getUrl())
        ->assertSuccessful();

    $this->actingAs($editor)
        ->get(EditTeamMember::getUrl(['record' => $member->getKey()]))
        ->assertSuccessful();
});

it('restricts deleting a team member to Super Admin and Website Manager', function () {
    $superAdmin = teamMemberPanelUser(UserRole::SuperAdmin);
    $websiteManager = teamMemberPanelUser(UserRole::WebsiteManager);
    $editor = teamMemberPanelUser(UserRole::Editor);
    $member = TeamMember::factory()->create();

    expect($superAdmin->can('delete', $member))->toBeTrue()
        ->and($websiteManager->can('delete', $member))->toBeTrue()
        ->and($editor->can('delete', $member))->toBeFalse();
});

it('lets an admin edit and re-save a team member that already has a photo, without re-entering alt text', function () {
    // Regression guard: SpatieMediaLibraryFileUpload::loadStateFromRelationshipsUsing
    // populates 'photo' with the existing media's uuid on every Edit page
    // load, which makes photo_alt's `required(fn ($get) =>
    // filled($get('photo')))` condition true from the moment the page opens
    // -- before anyone touches the form -- while the alt field itself would
    // render blank unless afterStateHydrated() pulls it from the record.
    // Same shape as ProgrammeResourceTest's equivalent guard (Task 8 brief
    // Trap 2): confidence from reading the pattern is not a substitute for
    // exercising it, which is what shipped the underlying bug in Spec 1.
    Storage::fake('public');
    $admin = teamMemberPanelUser(UserRole::SuperAdmin);

    Livewire::actingAs($admin)
        ->test(CreateTeamMember::class)
        ->fillForm([
            'name' => 'Alt Hydration Person',
            'role' => 'Environment Officer',
            'photo' => UploadedFile::fake()->image('officer.jpg'),
            'photo_alt' => 'Portrait of the environment officer',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $member = TeamMember::where('name', 'Alt Hydration Person')->firstOrFail();

    // The real Filament upload path -- distinct from the model-level disk
    // test in TeamMemberTest.php, which cannot exercise Filament's disk
    // fallback (see that test's comment for why).
    expect($member->getFirstMedia('photo')->disk)->toBe('public');

    // Edit the record and change only the name -- do not touch alt text.
    Livewire::actingAs($admin)
        ->test(EditTeamMember::class, ['record' => $member->getRouteKey()])
        ->fillForm([
            'name' => 'Alt Hydration Person (Updated)',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($member->fresh()->name)->toBe('Alt Hydration Person (Updated)')
        ->and($member->fresh()->photoAlt())->toBe('Portrait of the environment officer');
});
