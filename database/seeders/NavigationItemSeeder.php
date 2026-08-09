<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use Illuminate\Database\Seeder;

class NavigationItemSeeder extends Seeder
{
    public function run(): void
    {
        // Six top-level items, regrouped 2026-08-08 — twelve was too many.
        // Their children are reached from each section's landing page, not
        // from a dropdown. See spec §3a.
        $items = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About Us', 'url' => '/about'],
            ['label' => 'Our Work', 'url' => '/our-work'],
            ['label' => 'News & Events', 'url' => '/news'],
            ['label' => 'Resources', 'url' => '/resources'],
            ['label' => 'Contact Us', 'url' => '/contact'],
        ];

        foreach ($items as $index => $item) {
            // Keyed on label, not url: the same reasoning as
            // QuickLinkSeeder — these URLs are placeholders that later
            // specs will change, and keying on url would insert a
            // duplicate row instead of updating the existing one, leaving
            // a stale, still-active link live in the header.
            NavigationItem::updateOrCreate(
                ['label' => $item['label']],
                [...$item, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
