<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProgrammeFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(15),
            'body' => fake()->paragraphs(3, true),
            'is_featured' => false,
            'sort_order' => 0,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'is_demo' => true,
        ];
    }
}
