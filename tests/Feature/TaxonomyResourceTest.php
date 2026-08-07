<?php

use App\Enums\UserRole;
use App\Filament\Resources\DocumentCategories\Pages\CreateDocumentCategory;
use App\Filament\Resources\NewsCategories\Pages\CreateNewsCategory;
use App\Filament\Resources\NewsCategories\Pages\EditNewsCategory;
use App\Models\DocumentCategory;
use App\Models\NewsCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate(UserRole::Viewer->value);

    $user = User::factory()->create();
    $user->assignRole(UserRole::Viewer->value);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('rejects a duplicate news category slug with a validation error instead of a server error', function () {
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
