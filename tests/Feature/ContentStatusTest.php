<?php

use App\Enums\ContentStatus;
use App\Models\Programme;
use Carbon\Carbon;

it('includes only published records with a past publish date', function () {
    Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Visible programme',
    ]);

    foreach ([ContentStatus::Draft, ContentStatus::UnderReview, ContentStatus::Archived] as $hidden) {
        Programme::factory()->create([
            'status' => $hidden,
            'published_at' => now()->subDay(),
        ]);
    }

    Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->addWeek(),
        'title' => 'Scheduled programme',
    ]);

    Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => null,
    ]);

    $visible = Programme::published()->get();

    expect($visible)->toHaveCount(1)
        ->and($visible->first()->title)->toBe('Visible programme');
});

it('never leaks a future-dated article', function () {
    Programme::factory()->create([
        'status' => ContentStatus::Published,
        'published_at' => now()->addMinute(),
    ]);

    expect(Programme::published()->count())->toBe(0);
});

it('agrees with itself at the exact instant published_at equals now', function () {
    $frozenNow = Carbon::parse('2026-01-01 12:00:00');
    Carbon::setTestNow($frozenNow);

    try {
        $programme = Programme::factory()->create([
            'status' => ContentStatus::Published,
            'published_at' => $frozenNow->copy(),
        ]);

        expect(Programme::published()->count())->toBe(1)
            ->and($programme->isPublished())->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});
