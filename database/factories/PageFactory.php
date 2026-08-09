<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'parent_id' => null,
            'intro' => fake()->sentence(12),
            'content' => [],
            'sort_order' => 0,
            'show_in_section_nav' => true,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'is_demo' => true,
        ];
    }
}
