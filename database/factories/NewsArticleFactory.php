<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NewsArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(15),
            'body' => fake()->paragraphs(3, true),
            'news_category_id' => null,
            'author_name' => null,
            'is_featured' => false,
            'status' => ContentStatus::Published,
            'published_at' => now()->subDay(),
            'share_to_facebook' => false,
            'is_demo' => true,
        ];
    }
}
