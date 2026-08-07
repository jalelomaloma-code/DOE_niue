<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(15),
            'body' => fake()->paragraphs(3, true),
            'programme_id' => null,
            'project_status' => ProjectStatus::Active,
            'start_date' => null,
            'end_date' => null,
            'is_featured' => false,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'is_demo' => true,
        ];
    }
}
