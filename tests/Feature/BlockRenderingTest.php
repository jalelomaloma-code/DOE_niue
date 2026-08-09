<?php

use Illuminate\Support\Facades\Blade;
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
