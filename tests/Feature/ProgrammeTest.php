<?php

use App\Models\Programme;

it('filters featured programmes', function () {
    Programme::factory()->count(3)->create();
    Programme::factory()->create(['is_featured' => true, 'title' => 'Marine Conservation']);

    expect(Programme::published()->featured()->count())->toBe(1);
});

it('falls back to the summary for the SEO description', function () {
    $programme = Programme::factory()->create([
        'summary' => 'Protecting Niue reef systems.',
        'seo_description' => null,
    ]);

    expect($programme->seoDescription())->toBe('Protecting Niue reef systems.');
});
