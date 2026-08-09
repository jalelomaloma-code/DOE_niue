<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use App\Support\DemoImageGenerator;
use Illuminate\Database\Seeder;

/**
 * Seeds four team members for the "Our Team" page's team_grid block.
 *
 * Names and roles are unmistakably fictional -- these are placeholder people
 * on a government website, and nobody reviewing the demo site should mistake
 * them for real Department staff. Roles are generic titles, not tied to any
 * real person or appointment.
 *
 * Photos are deterministic placeholder graphics from DemoImageGenerator
 * (reused from DemoContentSeeder's pattern), attached via the 'photo' media
 * collection with a required `alt` custom property.
 */
class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'name' => 'Talia Fifita-Brown',
                'role' => 'Director',
                'bio' => 'Leads the Department\'s environmental programmes and oversees its day-to-day operations.',
                'email' => 'director@example.test',
                'palette' => 'marine',
            ],
            [
                'name' => 'Semisi Kavaliku',
                'role' => 'Senior Environment Officer',
                'bio' => 'Coordinates conservation and biodiversity programmes across the Department.',
                'email' => 'senior.officer@example.test',
                'palette' => 'forest',
            ],
            [
                'name' => 'Losana Havili',
                'role' => 'Waste Management Officer',
                'bio' => 'Manages waste collection, recycling services and hazardous waste guidance.',
                'email' => 'waste.officer@example.test',
                'palette' => 'waste',
            ],
            [
                'name' => 'Petelo Manu',
                'role' => 'Communications Officer',
                'bio' => 'Handles public communications, news updates and community engagement.',
                'email' => 'communications@example.test',
                'palette' => 'climate',
            ],
        ];

        foreach ($members as $index => $data) {
            $member = TeamMember::create([
                'name' => $data['name'],
                'role' => $data['role'],
                'bio' => $data['bio'],
                'email' => $data['email'],
                'sort_order' => $index,
                'is_active' => true,
                'is_demo' => true,
            ]);

            $photoPath = DemoImageGenerator::make($data['palette'], 'DEMO - '.$data['role'], 600, 600);

            $member->addMedia($photoPath)
                ->preservingOriginal()
                ->withCustomProperties(['alt' => "Placeholder photo of {$data['name']}, {$data['role']} (demo content)"])
                ->toMediaCollection('photo');
        }
    }
}
