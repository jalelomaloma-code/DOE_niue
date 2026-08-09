<?php

use App\Enums\ContentStatus;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\User;

it('uses the author name as the byline when one is set', function () {
    $article = NewsArticle::factory()->create(['author_name' => 'Media Unit']);

    expect($article->byline())->toBe('Media Unit');
});

it('falls back to the creator name when no author is set', function () {
    $user = User::factory()->create(['name' => 'Jale Lomaloma']);

    $this->actingAs($user);
    $article = NewsArticle::factory()->create(['author_name' => null]);

    expect($article->fresh()->byline())->toBe('Jale Lomaloma');
});

it('falls back to the Department of Environment when there is no author and no creator', function () {
    $article = NewsArticle::factory()->create(['author_name' => null, 'created_by' => null]);

    expect($article->fresh()->byline())->toBe('Department of Environment');
});

it('belongs to a category', function () {
    $category = NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);
    $article = NewsArticle::factory()->create(['news_category_id' => $category->id]);

    expect($article->category->name)->toBe('Announcements');
});

it('casts status to ContentStatus and reports isPublished correctly on a freshly loaded record', function () {
    $article = NewsArticle::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $fresh = NewsArticle::findOrFail($article->id);

    expect($fresh->status)->toBe(ContentStatus::Published)
        ->and($fresh->isPublished())->toBeTrue();
});

// See ProgrammeTest for why both assertions are load-bearing on this path.
it('strips a script tag and unwraps a pasted h1 from the body on save', function () {
    $article = NewsArticle::factory()->create([
        'body' => '<h1>Pasted Heading</h1><p>Safe</p><script>alert(1)</script>',
    ]);

    $stored = NewsArticle::findOrFail($article->id)->body;

    expect($stored)->not->toContain('<script')
        ->and($stored)->not->toContain('<h1')
        ->and($stored)->toContain('Pasted Heading')
        ->and($stored)->toContain('Safe');
});
