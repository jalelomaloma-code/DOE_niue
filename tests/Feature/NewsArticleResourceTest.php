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

it('rejects a featured image larger than the 5MB limit', function () {
    // Featured images had no maxSize() of their own until the Document
    // resource's 20MB upload requirement forced config/livewire.php and
    // config/media-library.php's global upload ceilings up from their
    // package defaults (12MB / 10MB) to 25MB each -- which, as a side
    // effect, quietly raised every OTHER upload field (including this one)
    // to the same 25MB ceiling. This field-level maxSize(5120) closes that
    // gap and keeps featured images tighter than before, not looser.
    // This test's ~6MB buffer, combined with DocumentResourceTest's own
    // ~20MB oversized-upload test in the same PHPUnit process, can exceed
    // PHP CLI's default 128M memory_limit when the whole suite runs
    // together (large buffers from earlier tests aren't fully released
    // before later tests start). Raised locally rather than in
    // phpunit.xml, since only these two deliberately-oversized-upload
    // tests need the headroom.
    ini_set('memory_limit', '256M');

    Storage::fake('public');
    $admin = newsArticlePanelAdmin();
    $category = NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);

    // A real (GD-rendered) PNG signature padded with null bytes past the
    // IEND chunk. getimagesize()/mime_content_type() still read this as a
    // genuine 10x10 image/png (verified independently with a standalone
    // script), so this exercises maxSize() specifically -- not the
    // image-type rule, which a garbage payload would trip instead.
    ob_start();
    imagepng(imagecreatetruecolor(10, 10));
    $png = ob_get_clean();
    $oversizedImage = UploadedFile::fake()->createWithContent(
        'huge-hero.png',
        $png.str_repeat("\0", 6 * 1024 * 1024) // ~6MB, over the 5MB (5120KB) limit
    );

    Livewire::actingAs($admin)
        ->test(CreateNewsArticle::class)
        ->fillForm([
            'title' => 'Oversized Hero Image',
            'slug' => 'oversized-hero-image',
            'excerpt' => 'Too large a hero image.',
            'news_category_id' => $category->id,
            'status' => ContentStatus::Draft->value,
            'featured_image' => $oversizedImage,
            'featured_image_alt' => 'A very large image',
        ])
        ->call('create')
        // maxSize()/acceptedFileTypes() are each registered via
        // CanBeValidated::rule(Closure) and ARE eagerly evaluated into a
        // plain "max:5120" string (CanBeValidated::getValidationRules()).
        // But BaseFileUpload::getValidationRules() (which every file-upload
        // field uses instead of the plain trait behaviour) then collects
        // those per-file string rules and re-validates them itself, inside
        // a single outer closure it adds to the field's rule set
        // (BaseFileUpload.php ~752-771): it runs its own nested
        // Validator::make() against the real file and, on failure, calls
        // the outer $fail() with just the message text. Laravel's outer
        // validator only ever sees that one opaque closure, so it records
        // the failure under Illuminate\Validation\ClosureValidationRule --
        // confirmed empirically here, not assumed. There is no inner rule
        // name ('max' or 'mimetypes') left to assert against by the time it
        // reaches the outer failedRules(); the message is the only signal.
        ->assertHasFormErrors([
            'featured_image' => fn ($failedRules, $messages) => str_contains($messages[0] ?? '', 'kilobytes'),
        ]);

    expect(NewsArticle::where('slug', 'oversized-hero-image')->exists())->toBeFalse();
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
