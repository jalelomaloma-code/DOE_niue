<?php

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use App\Models\DocumentCategory;
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

function documentPanelAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::SuperAdmin->value);

    return $user;
}

function documentPanelEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::Editor->value);

    return $user;
}

// A minimal-but-genuine PDF payload. Spatie MediaLibrary and Laravel's
// `mimetypes` validation rule both sniff real file content rather than
// trusting a client-declared MIME type, so `UploadedFile::fake()->create()`
// (which never writes real bytes to its temp file -- see DocumentTest.php)
// cannot stand in for a real upload anywhere in this suite.
function fakePdf(string $name = 'report.pdf', int $extraKilobytes = 100): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".str_repeat('A', $extraKilobytes * 1024));
}

it('lists documents in the panel', function () {
    $admin = documentPanelAdmin();
    Document::factory()->count(2)->create();

    Livewire::actingAs($admin)
        ->test(ListDocuments::class)
        ->assertSuccessful();
});

it('creates a document with a category and an attached PDF', function () {
    Storage::fake('public');
    $admin = documentPanelAdmin();
    $category = DocumentCategory::create(['name' => 'Reports', 'slug' => 'reports', 'sort_order' => 1]);

    Livewire::actingAs($admin)
        ->test(CreateDocument::class)
        ->fillForm([
            'title' => 'State of the Environment Report',
            'slug' => 'state-of-the-environment-report',
            'description' => 'Annual report on environmental conditions.',
            'document_category_id' => $category->id,
            'status' => ContentStatus::Draft->value,
            'file' => fakePdf(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $document = Document::where('slug', 'state-of-the-environment-report')->firstOrFail();

    expect($document->category->name)->toBe('Reports')
        ->and($document->fileType())->toBe('PDF');
});

it('rejects an image upload as a disallowed document MIME type', function () {
    // A genuine PNG (UploadedFile::fake()->image() writes real GD-rendered
    // bytes, not just a declared MIME string) is not in the accepted list
    // of document types (PDF/Word/Excel), so it must be rejected.
    Storage::fake('public');
    $admin = documentPanelAdmin();

    Livewire::actingAs($admin)
        ->test(CreateDocument::class)
        ->fillForm([
            'title' => 'Disguised Image Upload',
            'slug' => 'disguised-image-upload',
            'status' => ContentStatus::Draft->value,
            'file' => UploadedFile::fake()->image('malware.png'),
        ])
        ->call('create')
        // acceptedFileTypes()/maxSize() are registered via Filament's
        // ->rule(Closure) rather than plain "mimetypes:..."/"max:..."
        // strings, so the validator records the failed rule under
        // Illuminate\Validation\ClosureValidationRule -- there is no rule
        // name to assert against. The message text is the only signal
        // that this specific rule (not some other one) is what fired.
        ->assertHasFormErrors([
            'file' => fn ($failedRules, $messages) => str_contains($messages[0] ?? '', 'file of type'),
        ]);

    expect(Document::where('slug', 'disguised-image-upload')->exists())->toBeFalse();
});

it('rejects a file larger than the 20MB limit', function () {
    Storage::fake('public');
    $admin = documentPanelAdmin();

    Livewire::actingAs($admin)
        ->test(CreateDocument::class)
        ->fillForm([
            'title' => 'Oversized Report',
            'slug' => 'oversized-report',
            'status' => ContentStatus::Draft->value,
            'file' => fakePdf('big-report.pdf', 20 * 1024 + 64), // > 20480 KB
        ])
        ->call('create')
        ->assertHasFormErrors([
            'file' => fn ($failedRules, $messages) => str_contains($messages[0] ?? '', 'kilobytes'),
        ]);

    expect(Document::where('slug', 'oversized-report')->exists())->toBeFalse();
});

it('hides the Published option from Editors but keeps it enabled for admins', function () {
    // Two halves of "Editors cannot publish": this is the usability half
    // (options filtering). The security half is covered separately below,
    // via the publish policy ability. Both must hold for the constraint to
    // actually be enforced, not merely suggested by the UI.
    $editor = documentPanelEditor();
    $admin = documentPanelAdmin();

    Livewire::actingAs($editor)
        ->test(CreateDocument::class)
        ->assertFormFieldExists('status', function ($field) {
            /** @var Select $field */
            return ! array_key_exists(ContentStatus::Published->value, $field->getOptions());
        });

    Livewire::actingAs($admin)
        ->test(CreateDocument::class)
        ->assertFormFieldExists('status', function ($field) {
            /** @var Select $field */
            return array_key_exists(ContentStatus::Published->value, $field->getOptions());
        });
});

it('lets an editor update but not delete or publish a document, and lets an admin do both', function () {
    $editor = documentPanelEditor();
    $admin = documentPanelAdmin();
    $document = Document::factory()->create();

    expect($editor->can('update', $document))->toBeTrue()
        ->and($editor->can('delete', $document))->toBeFalse()
        ->and($editor->can('publish', $document))->toBeFalse()
        ->and($admin->can('delete', $document))->toBeTrue()
        ->and($admin->can('publish', $document))->toBeTrue();

    Livewire::actingAs($editor)
        ->test(EditDocument::class, ['record' => $document->getRouteKey()])
        ->assertSuccessful();
});
