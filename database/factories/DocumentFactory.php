<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(15),
            'document_category_id' => null,
            'published_date' => now()->subDay()->toDateString(),
            'is_featured' => false,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'is_demo' => true,
        ];
    }
}
