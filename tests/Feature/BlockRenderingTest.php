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

it('renders nothing for an empty block list', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => []]);

    expect(trim($html))->toBe('');
});

it('renders nothing when blocks are null', function () {
    $html = Blade::render('<x-page.content :blocks="$blocks" />', ['blocks' => null]);

    expect(trim($html))->toBe('');
});
