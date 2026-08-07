<?php

use App\Models\DocumentCategory;
use App\Models\NewsCategory;
use Database\Seeders\DocumentCategorySeeder;

it('stores a news category with a unique slug', function () {
    NewsCategory::create(['name' => 'Announcements', 'slug' => 'announcements']);

    expect(NewsCategory::where('slug', 'announcements')->exists())->toBeTrue();
});

it('stores the six seeded document categories in order', function () {
    $this->seed(DocumentCategorySeeder::class);

    expect(DocumentCategory::orderBy('sort_order')->pluck('name')->all())
        ->toBe(['Publications', 'Policies', 'Legislation', 'Forms', 'Reports', 'Downloads']);
});
