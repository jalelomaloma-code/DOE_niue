<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => fake()->jobTitle(),
            'bio' => fake()->sentence(15),
            'email' => fake()->unique()->safeEmail(),
            'sort_order' => 0,
            'is_active' => true,
            'is_demo' => true,
        ];
    }
}
