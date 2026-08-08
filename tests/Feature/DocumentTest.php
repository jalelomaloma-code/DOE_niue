<?php

use App\Enums\ContentStatus;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('reports file size and type from the attached file', function () {
    Storage::fake('public');

    // UploadedFile::fake()->create() (as in the plan) sets a virtual
    // getSize()/getMimeType() but never writes real bytes to the temp
    // file — see Illuminate\Http\Testing\FileFactory::create(), which
    // only assigns sizeToReport/mimeTypeToReport. Spatie MediaLibrary
    // ignores those virtual values and derives both size and mime type
    // from the real file on disk (FileAdder::toMediaCollection(), which
    // calls filesize() and File::getMimeType() against $pathToFile) to
    // guard against a client spoofing its declared mime type. Against a
    // literal 0-byte temp file that always sniffs as application/x-empty
    // and is rejected by acceptsMimeTypes() -- regardless of platform.
    // createWithContent() writes a real PDF-signature payload so the
    // library's content sniffing genuinely detects application/pdf.
    $document = Document::factory()->create(['title' => 'State of the Environment Report']);
    $document->addMedia(UploadedFile::fake()->createWithContent('report.pdf', "%PDF-1.4\n".str_repeat('A', 250 * 1024)))
        ->toMediaCollection('file');

    expect($document->fresh()->fileType())->toBe('PDF')
        ->and($document->fresh()->fileSizeForHumans())->toContain('KB');
});

it('returns null size when no file is attached', function () {
    $document = Document::factory()->create();

    expect($document->fileSizeForHumans())->toBeNull()
        ->and($document->fileType())->toBeNull();
});

it('belongs to a category', function () {
    $category = DocumentCategory::create(['name' => 'Reports', 'slug' => 'reports', 'sort_order' => 1]);
    $document = Document::factory()->create(['document_category_id' => $category->id]);

    expect($document->category->name)->toBe('Reports');
});

it('casts status to ContentStatus and reports isPublished correctly on a freshly loaded record', function () {
    $document = Document::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    // Deliberately re-fetch from the database rather than asserting on the
    // in-memory $document. PHP backed enums are singletons, so an
    // in-memory instance would report ContentStatus::Published whether or
    // not the cast is actually configured -- only a fresh hydration from
    // the raw 'status' string column exercises the cast.
    $fresh = Document::findOrFail($document->id);

    expect($fresh->status)->toBe(ContentStatus::Published)
        ->and($fresh->isPublished())->toBeTrue();
});
