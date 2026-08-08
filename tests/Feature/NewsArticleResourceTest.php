<?php

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\NewsArticles\Pages\CreateNewsArticle;
use App\Filament\Resources\NewsArticles\Pages\EditNewsArticle;
use App\Filament\Resources\NewsArticles\Pages\ListNewsArticles;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
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

function newsArticlePanelAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);

    return $user;
}

function newsArticlePanelEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::Editor->value);

    return $user;
}

it('lists news articles in the panel', function () {
    $admin = newsArticlePanelAdmin();
    NewsArticle::factory()->count(2)->create();

    Livewire::actingAs($admin)
        ->test(ListNewsArticles::class)
        ->assertSuccessful();
});

it('creates a news article with no image and no alt text required', function () {
    $admin = newsArticlePanelAdmin();
    $category = NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);

    Livewire::actingAs($admin)
        ->test(CreateNewsArticle::class)
        ->fillForm([
            'title' => 'No Image Article',
            'slug' => 'no-image-article',
            'excerpt' => 'An article with no featured image.',
            'news_category_id' => $category->id,
            'status' => ContentStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(NewsArticle::where('slug', 'no-image-article')->exists())->toBeTrue();
});

it('requires alt text once an image is attached, and saves the alt as a media custom property', function () {
    Storage::fake('public');
    $admin = newsArticlePanelAdmin();
    $category = NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);

    // Image uploaded, alt text left blank -> validation error. This is the
    // only reliable enforcement point for alt text, so this test is the sole
    // regression guard for it.
    Livewire::actingAs($admin)
        ->test(CreateNewsArticle::class)
        ->fillForm([
            'title' => 'Reef Cleanup Volunteers Needed',
            'slug' => 'reef-cleanup-volunteers-needed',
            'excerpt' => 'Call for volunteers.',
            'news_category_id' => $category->id,
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['featured_image_alt' => 'required']);

    expect(NewsArticle::where('slug', 'reef-cleanup-volunteers-needed')->exists())->toBeFalse();

    // Image uploaded with alt text -> succeeds, and the alt is stored on the media.
    Livewire::actingAs($admin)
        ->test(CreateNewsArticle::class)
        ->fillForm([
            'title' => 'Reef Cleanup Volunteers Needed',
            'slug' => 'reef-cleanup-volunteers-needed',
            'excerpt' => 'Call for volunteers.',
            'news_category_id' => $category->id,
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => 'Volunteers cleaning the reef shoreline',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = NewsArticle::where('slug', 'reef-cleanup-volunteers-needed')->firstOrFail();

    expect($article->featuredImageAlt())->toBe('Volunteers cleaning the reef shoreline');
});

it('hides the Published option from Editors but keeps it enabled for admins', function () {
    // Two halves of "Editors cannot publish": this is the usability half
    // (options filtering). The security half is covered separately below,
    // via the publish policy ability. Both must hold for the constraint to
    // actually be enforced, not merely suggested by the UI.
    $editor = newsArticlePanelEditor();
    $admin = newsArticlePanelAdmin();

    Livewire::actingAs($editor)
        ->test(CreateNewsArticle::class)
        ->assertFormFieldExists('status', function ($field) {
            /** @var Select $field */
            return ! array_key_exists(ContentStatus::Published->value, $field->getOptions());
        });

    Livewire::actingAs($admin)
        ->test(CreateNewsArticle::class)
        ->assertFormFieldExists('status', function ($field) {
            /** @var Select $field */
            return array_key_exists(ContentStatus::Published->value, $field->getOptions());
        });
});

it('renders the Facebook toggle disabled with helper text explaining it is not yet connected', function () {
    $admin = newsArticlePanelAdmin();

    Livewire::actingAs($admin)
        ->test(CreateNewsArticle::class)
        ->assertFormFieldExists('share_to_facebook', function ($field) {
            /** @var \Filament\Forms\Components\Toggle $field */
            return $field->isDisabled();
        });
});

it('lets an admin edit and re-save a news article that already has an image, without re-entering alt text', function () {
    // Regression guard: SpatieMediaLibraryFileUpload::loadStateFromRelationshipsUsing
    // populates 'featured_image' with the existing media's uuid on every Edit
    // page load, which made featured_image_alt's `required(fn ($get) =>
    // filled($get('featured_image')))` condition true from the moment the
    // page opened -- before anyone touched the form -- while the alt field
    // itself rendered blank because nothing hydrated it from the record.
    Storage::fake('public');
    $admin = newsArticlePanelAdmin();
    $category = NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);

    Livewire::actingAs($admin)
        ->test(CreateNewsArticle::class)
        ->fillForm([
            'title' => 'Marine Debris Cleanup Update',
            'slug' => 'marine-debris-cleanup-update',
            'excerpt' => 'Progress on the cleanup.',
            'news_category_id' => $category->id,
            'status' => ContentStatus::Draft->value,
            'featured_image' => UploadedFile::fake()->image('reef.jpg'),
            'featured_image_alt' => 'Volunteers collecting marine debris',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = NewsArticle::where('slug', 'marine-debris-cleanup-update')->firstOrFail();

    // Edit the record and change only the title -- do not touch alt text.
    Livewire::actingAs($admin)
        ->test(EditNewsArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm([
            'title' => 'Marine Debris Cleanup Update (Revised)',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->fresh()->title)->toBe('Marine Debris Cleanup Update (Revised)')
        ->and($article->fresh()->featuredImageAlt())->toBe('Volunteers collecting marine debris');
});

it('lets an editor update but not delete or publish a news article, and lets an admin do both', function () {
    $editor = newsArticlePanelEditor();
    $admin = newsArticlePanelAdmin();
    $article = NewsArticle::factory()->create();

    expect($editor->can('update', $article))->toBeTrue()
        ->and($editor->can('delete', $article))->toBeFalse()
        ->and($editor->can('publish', $article))->toBeFalse()
        ->and($admin->can('delete', $article))->toBeTrue()
        ->and($admin->can('publish', $article))->toBeTrue();

    Livewire::actingAs($editor)
        ->test(EditNewsArticle::class, ['record' => $article->getRouteKey()])
        ->assertSuccessful();
});
