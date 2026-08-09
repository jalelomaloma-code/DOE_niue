<?php

use App\Enums\ContentStatus;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Programme;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

it('renders a rich text block', function () {
    $html = Blade::render(
        '<x-page.content :blocks="$blocks" />',
        ['blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<p>Waste services</p>']],
        ]]
    );

    expect($html)->toContain('Waste services');
});

it('skips an unknown block type without fataling', function () {
    Log::spy();

    $html = Blade::render(
        '<x-page.content :blocks="$blocks" />',
        ['blocks' => [
            ['type' => 'no_such_block', 'data' => []],
            ['type' => 'rich_text', 'data' => ['body' => '<p>Still here</p>']],
        ]]
    );

    expect($html)->toContain('Still here');
    Log::shouldHaveReceived('warning')->once();
});

it('skips a block whose type is not a string, without fataling', function () {
    Log::spy();

    // BlockType::tryFrom() only accepts a string. PHP's weak typing would
    // coerce an int/float/bool to a string, but not an array — that throws
    // a TypeError instead of returning null. A malformed 'type' key (e.g.
    // corrupt or hand-edited JSON) must still be skipped gracefully rather
    // than fatal the whole page.
    $html = Blade::render(
        '<x-page.content :blocks="$blocks" />',
        ['blocks' => [
            ['type' => ['unexpected' => 'array'], 'data' => []],
            ['type' => 'rich_text', 'data' => ['body' => '<p>Still here</p>']],
        ]]
    );

    expect($html)->toContain('Still here');
    Log::shouldHaveReceived('warning')->once();
});

it('renders nothing for an empty block list', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => []]);

    expect(trim($html))->toBe('');
});

it('renders nothing when blocks are null', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => null]);

    expect(trim($html))->toBe('');
});

it('renders an image block with its alt text and caption', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'image', 'data' => [
            'url' => '/storage/demo.jpg',
            'alt' => 'Coastline at dawn',
            'caption' => 'The northern coast',
        ]],
    ]]);

    expect($html)->toContain('alt="Coastline at dawn"')
        ->and($html)->toContain('The northern coast');
});

it('renders a callout with its tone', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'callout', 'data' => ['heading' => 'Note', 'body' => 'Collection changes', 'tone' => 'warning']],
    ]]);

    // 'Note' and 'Collection changes' come from heading/body regardless of
    // tone — the load-bearing assertion is the accent class actually firing.
    expect($html)->toContain('Note')
        ->and($html)->toContain('Collection changes')
        ->and($html)->toContain('bg-accent');
});

it('does not apply the accent tone to an info callout', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'callout', 'data' => ['heading' => 'Note', 'body' => 'Collection changes', 'tone' => 'info']],
    ]]);

    // Pairs with the warning-tone test above: a callout view that hardcoded
    // bg-accent regardless of tone would pass the warning case alone.
    expect($html)->not->toContain('bg-accent');
});

it('renders a card grid and omits links for cards without a url', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'card_grid', 'data' => ['heading' => 'Services', 'cards' => [
            ['title' => 'Household', 'text' => 'Weekly collection', 'url' => '/waste-and-recycling'],
            ['title' => 'Green waste', 'text' => 'Monthly collection', 'url' => null],
        ]]],
    ]]);

    expect($html)->toContain('Household')
        ->and($html)->toContain('/waste-and-recycling');

    // Scope the "no link" assertion to the specific card without a url — a
    // blanket assertion that the whole page contains no <a> at all would
    // also fail against the first card's legitimate link. Split on each
    // <li> boundary (there's no nesting) rather than a lazy regex, which
    // would happily cross into the next card's </li> and swallow its <a>.
    $cards = preg_split('/(?=<li)/', $html);
    $greenWasteCard = collect($cards)->first(fn ($card) => str_contains($card, 'Green waste'));

    expect($greenWasteCard)->not->toBeNull()
        ->and($greenWasteCard)->not->toContain('<a');
});

it('renders an image block without a caption', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'image', 'data' => ['url' => '/storage/demo.jpg', 'alt' => 'Reef']],
    ]]);

    expect($html)->toContain('alt="Reef"');
});

it('logs a warning when an image block renders without alt text', function () {
    Log::spy();

    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'image', 'data' => ['url' => '/storage/demo.jpg', 'alt' => '']],
    ]]);

    expect($html)->toContain('alt=""');
    Log::shouldHaveReceived('warning')->once();
});

it('does not log a warning when an image block has alt text', function () {
    Log::spy();

    Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'image', 'data' => ['url' => '/storage/demo.jpg', 'alt' => 'Reef']],
    ]]);

    Log::shouldNotHaveReceived('warning');
});

it('lists only published documents', function () {
    $category = DocumentCategory::create(['name' => 'Forms', 'slug' => 'forms', 'sort_order' => 0]);

    Document::factory()->create([
        'title' => 'Visible Form',
        'document_category_id' => $category->id,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    Document::factory()->create([
        'title' => 'Draft Form',
        'document_category_id' => $category->id,
        'status' => ContentStatus::Draft,
    ]);

    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'documents_list', 'data' => ['heading' => 'Forms', 'category_id' => $category->id]],
    ]]);

    expect($html)->toContain('Visible Form')->and($html)->not->toContain('Draft Form');
});

it('filters documents by category, excluding published documents in a different category', function () {
    // The previous test alone can't prove the category_id filter runs at
    // all: both its documents share one category, so published() excludes
    // the draft regardless of whether filtering happens. This test uses
    // two published documents in two different categories, so only the
    // filter clause — not the status scope — can be responsible for the
    // "Other Category Doc" exclusion.
    $category = DocumentCategory::create(['name' => 'Forms', 'slug' => 'forms', 'sort_order' => 0]);
    $other = DocumentCategory::create(['name' => 'Reports', 'slug' => 'reports', 'sort_order' => 1]);

    Document::factory()->create([
        'title' => 'Forms Doc',
        'document_category_id' => $category->id,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    Document::factory()->create([
        'title' => 'Other Category Doc',
        'document_category_id' => $other->id,
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'documents_list', 'data' => ['category_id' => $category->id]],
    ]]);

    expect($html)->toContain('Forms Doc')->and($html)->not->toContain('Other Category Doc');
});

it('lists only published programmes', function () {
    Programme::factory()->create(['title' => 'Live Programme', 'status' => ContentStatus::Published, 'published_at' => now()->subDay()]);
    Programme::factory()->create(['title' => 'Hidden Programme', 'status' => ContentStatus::Draft]);

    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'programmes_list', 'data' => []],
    ]]);

    expect($html)->toContain('Live Programme')->and($html)->not->toContain('Hidden Programme');
});

it('renders contact details from site settings', function () {
    SiteSetting::current()->update(['email' => 'environment@mail.gov.nu', 'address' => 'Alofi, Niue']);

    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'contact_details', 'data' => ['heading' => 'Contact']],
    ]]);

    expect($html)->toContain('environment@mail.gov.nu')->and($html)->toContain('Alofi, Niue');
});

it('renders a documents block with no matching documents without error', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'documents_list', 'data' => ['heading' => 'Empty']],
    ]]);

    expect($html)->toContain('Empty');
});

it('renders a programmes block with no matching programmes without error', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'programmes_list', 'data' => ['heading' => 'No Programmes']],
    ]]);

    expect($html)->toContain('No Programmes');
});

it('renders contact details when every settings field is blank', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'contact_details', 'data' => ['heading' => 'Get in touch']],
    ]]);

    expect($html)->toContain('Get in touch');
});

it('clamps an absurd documents_list limit instead of running an unbounded query', function () {
    // Task 6's Filament form clamps this at the UI layer (maxValue(50)), but
    // that's usability only. A seeder, a raw DB write, or a hand-edited JSON
    // blob bypasses the form entirely — this project has already been bitten
    // by that exact split (document_category_id required in the form, but
    // nullable at the column). The block itself must not trust the number.
    //
    // Asserting against rendered row count would be meaningless with only a
    // handful of fixture rows (3 rows is <= 100 whether or not clamping
    // happens at all). Inspect the executed SQL's LIMIT clause instead — it
    // proves the clamp regardless of how much fixture data exists. Laravel's
    // query grammar compiles LIMIT as a literal integer in the SQL text
    // (Grammar::compileLimit returns 'limit '.(int) $limit), not as a bound
    // placeholder, so the assertion is against the query string, not bindings.
    DB::enableQueryLog();

    Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'documents_list', 'data' => ['limit' => 10000]],
    ]]);

    $query = collect(DB::getQueryLog())->first(fn ($q) => str_contains($q['query'], 'from "documents"'));

    expect($query)->not->toBeNull()
        ->and($query['query'])->toContain('limit 100')
        ->and($query['query'])->not->toContain('limit 10000');
});

it('clamps an absurd programmes_list limit instead of running an unbounded query', function () {
    DB::enableQueryLog();

    Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'programmes_list', 'data' => ['limit' => 10000]],
    ]]);

    $query = collect(DB::getQueryLog())->first(fn ($q) => str_contains($q['query'], 'from "programmes"'));

    expect($query)->not->toBeNull()
        ->and($query['query'])->toContain('limit 100')
        ->and($query['query'])->not->toContain('limit 10000');
});

it('casts a string limit from JSON instead of passing it through to the query builder unchanged', function () {
    // A block's data comes from JSON, where a Filament number field can
    // round-trip as a numeric string. min() on a string works "usually" via
    // PHP's loose comparison, but (int) cast makes the intent explicit and
    // protects the take() call, which requires a real int.
    DB::enableQueryLog();

    Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => [
        ['type' => 'documents_list', 'data' => ['limit' => '3']],
    ]]);

    $query = collect(DB::getQueryLog())->first(fn ($q) => str_contains($q['query'], 'from "documents"'));

    expect($query)->not->toBeNull()
        ->and($query['query'])->toContain('limit 3');
});
