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
//
// The 20MB variant used by the oversized-file test, combined with the
// featured-image suite's own oversized (~6MB) payload later in the same
// run, pushes the shared PHPUnit process past PHP CLI's default 128M
// memory_limit when the whole suite runs in one process (each test's
// large buffers aren't fully released before the next test starts). Raised
// locally here rather than in phpunit.xml, since it's only these two
// deliberately-oversized-upload tests that need the headroom.
function fakePdf(string $name = 'report.pdf', int $extraKilobytes = 100): UploadedFile
{
    ini_set('memory_limit', '256M');

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

it('requires a category, so a document cannot be saved uncategorised', function () {
    // The migration column stays nullable with nullOnDelete() -- that's
    // deliberate, so deleting a category doesn't destroy its documents.
    // This form-level requirement is the separate, save-time guard: Spec 3's
    // Resources page filters by category, so an uncategorised document
    // would be published but invisible in every filtered view.
    Storage::fake('public');
    $admin = documentPanelAdmin();

    Livewire::actingAs($admin)
        ->test(CreateDocument::class)
        ->fillForm([
            'title' => 'Uncategorised Report',
            'slug' => 'uncategorised-report',
            'status' => ContentStatus::Draft->value,
            'file' => fakePdf(),
            'document_category_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['document_category_id' => 'required']);

    expect(Document::where('slug', 'uncategorised-report')->exists())->toBeFalse();
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
        // acceptedFileTypes()/maxSize() are each registered via
        // CanBeValidated::rule(Closure) and ARE eagerly evaluated into a
        // plain "mimetypes:..."/"max:..." string
        // (CanBeValidated::getValidationRules(), ~line 872: `$rule =
        // $this->evaluate($rule);`). But BaseFileUpload::getValidationRules()
        // (which every file-upload field uses instead of the plain trait
        // behaviour) collects those per-file string rules and re-validates
        // them itself inside a single outer closure it adds to the field's
        // rule set (BaseFileUpload.php ~752-771): it runs its own nested
        // Validator::make() against the real file and, on failure, calls the
        // outer $fail() with just the message text. Laravel's outer
        // validator only ever sees that one opaque closure, so it records
        // the failure under Illuminate\Validation\ClosureValidationRule --
        // confirmed empirically (dumped failedRules() and tried the
        // rule-name assertion directly), not assumed. There is no inner
        // rule name ('mimetypes' or 'max') left to assert against by the
        // time it reaches the outer failedRules(); the message is the only
        // signal that this specific rule, not some other one, fired.
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
