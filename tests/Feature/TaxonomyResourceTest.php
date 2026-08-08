<?php

use App\Enums\UserRole;
use App\Filament\Resources\DocumentCategories\Pages\CreateDocumentCategory;
use App\Filament\Resources\DocumentCategories\Pages\EditDocumentCategory;
use App\Filament\Resources\DocumentCategories\Pages\ListDocumentCategories;
use App\Filament\Resources\NewsCategories\Pages\CreateNewsCategory;
use App\Filament\Resources\NewsCategories\Pages\EditNewsCategory;
use App\Filament\Resources\NewsCategories\Pages\ListNewsCategories;
use App\Models\DocumentCategory;
use App\Models\NewsCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function taxonomyPanelUser(UserRole $role): User
{
    Role::findOrCreate($role->value);

    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('rejects a duplicate news category slug with a validation error instead of a server error', function () {
    // Editor, not Viewer: since NewsCategoryPolicy was added, Viewer can no
    // longer reach create/edit at all (see the refusal tests below), so this
    // form-validation test needs a role actually permitted to create.
    $this->actingAs(taxonomyPanelUser(UserRole::Editor));

    NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);

    Livewire::test(CreateNewsCategory::class)
        ->fillForm([
            'name' => 'Announcements Again',
            'slug' => 'announcements',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);

    expect(NewsCategory::where('slug', 'announcements')->count())->toBe(1);
});

it('rejects a duplicate document category slug with a validation error instead of a server error', function () {
    $this->actingAs(taxonomyPanelUser(UserRole::Editor));

    DocumentCategory::create(['name' => 'Publications', 'slug' => 'publications']);

    Livewire::test(CreateDocumentCategory::class)
        ->fillForm([
            'name' => 'Publications Again',
            'slug' => 'publications',
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);

    expect(DocumentCategory::where('slug', 'publications')->count())->toBe(1);
});

it('lets a news category be edited and saved unchanged without tripping its own uniqueness rule', function () {
    $this->actingAs(taxonomyPanelUser(UserRole::Editor));

    $category = NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);

    Livewire::test(EditNewsCategory::class, ['record' => $category->getKey()])
        ->fillForm([
            'name' => 'Announcements',
            'slug' => 'announcements',
            'sort_order' => 0,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(NewsCategory::where('slug', 'announcements')->count())->toBe(1);
});

it('blocks a viewer from creating, updating or deleting a news category, at the Gate level and the HTTP level', function () {
    $viewer = taxonomyPanelUser(UserRole::Viewer);
    $editor = taxonomyPanelUser(UserRole::Editor);
    $category = NewsCategory::create(['name' => 'Existing', 'slug' => 'existing']);

    expect($viewer->can('create', NewsCategory::class))->toBeFalse()
        ->and($viewer->can('update', $category))->toBeFalse()
        ->and($viewer->can('delete', $category))->toBeFalse()
        ->and($editor->can('create', NewsCategory::class))->toBeTrue()
        ->and($editor->can('update', $category))->toBeTrue()
        ->and($editor->can('delete', $category))->toBeFalse();

    $this->actingAs($viewer)
        ->get(CreateNewsCategory::getUrl())
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(EditNewsCategory::getUrl(['record' => $category->getKey()]))
        ->assertForbidden();

    $this->actingAs($editor)
        ->get(CreateNewsCategory::getUrl())
        ->assertSuccessful();

    $this->actingAs($editor)
        ->get(EditNewsCategory::getUrl(['record' => $category->getKey()]))
        ->assertSuccessful();
});

it('blocks a viewer from creating, updating or deleting a document category, at the Gate level and the HTTP level', function () {
    $viewer = taxonomyPanelUser(UserRole::Viewer);
    $editor = taxonomyPanelUser(UserRole::Editor);
    $category = DocumentCategory::create(['name' => 'Existing', 'slug' => 'existing']);

    expect($viewer->can('create', DocumentCategory::class))->toBeFalse()
        ->and($viewer->can('update', $category))->toBeFalse()
        ->and($viewer->can('delete', $category))->toBeFalse()
        ->and($editor->can('create', DocumentCategory::class))->toBeTrue()
        ->and($editor->can('update', $category))->toBeTrue()
        ->and($editor->can('delete', $category))->toBeFalse();

    $this->actingAs($viewer)
        ->get(CreateDocumentCategory::getUrl())
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(EditDocumentCategory::getUrl(['record' => $category->getKey()]))
        ->assertForbidden();

    $this->actingAs($editor)
        ->get(CreateDocumentCategory::getUrl())
        ->assertSuccessful();

    $this->actingAs($editor)
        ->get(EditDocumentCategory::getUrl(['record' => $category->getKey()]))
        ->assertSuccessful();
});

it('lets super admin and website manager delete categories, but not editor', function () {
    $superAdmin = taxonomyPanelUser(UserRole::SuperAdmin);
    $websiteManager = taxonomyPanelUser(UserRole::WebsiteManager);
    $editor = taxonomyPanelUser(UserRole::Editor);
    $newsCategory = NewsCategory::create(['name' => 'Existing', 'slug' => 'existing-news']);
    $documentCategory = DocumentCategory::create(['name' => 'Existing', 'slug' => 'existing-document']);

    expect($superAdmin->can('delete', $newsCategory))->toBeTrue()
        ->and($websiteManager->can('delete', $newsCategory))->toBeTrue()
        ->and($editor->can('delete', $newsCategory))->toBeFalse()
        ->and($superAdmin->can('delete', $documentCategory))->toBeTrue()
        ->and($websiteManager->can('delete', $documentCategory))->toBeTrue()
        ->and($editor->can('delete', $documentCategory))->toBeFalse();
});

it('still lets any panel role, including viewer, list categories', function (UserRole $role) {
    // viewAny stays open to every role -- only create/update/delete tightened.
    $user = taxonomyPanelUser($role);

    $this->actingAs($user)
        ->get(ListNewsCategories::getUrl())
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(ListDocumentCategories::getUrl())
        ->assertSuccessful();
})->with([
    'super admin' => [UserRole::SuperAdmin],
    'website manager' => [UserRole::WebsiteManager],
    'editor' => [UserRole::Editor],
    'viewer' => [UserRole::Viewer],
]);
