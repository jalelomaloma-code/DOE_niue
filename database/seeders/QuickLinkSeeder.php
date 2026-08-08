<?php

namespace Database\Seeders;

use App\Models\QuickLink;
use Illuminate\Database\Seeder;

class QuickLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['label' => 'Environment Programmes', 'icon' => 'heroicon-o-globe-alt',        'url' => '/environment-programmes',        'description' => 'Conservation, waste, climate and biodiversity work.'],
            ['label' => 'Waste & Recycling',      'icon' => 'heroicon-o-trash',            'url' => '/waste-and-recycling',           'description' => 'Waste services, recycling and disposal guidance.'],
            ['label' => 'Biodiversity',           'icon' => 'heroicon-o-sparkles',         'url' => '/biodiversity-and-conservation', 'description' => 'Protecting Niue\'s native species and habitats.'],
            ['label' => 'Climate & Marine',       'icon' => 'heroicon-o-cloud',            'url' => '/climate-and-marine',            'description' => 'Climate resilience and marine protection.'],
            ['label' => 'Publications',           'icon' => 'heroicon-o-document-text',    'url' => '/resources',                     'description' => 'Reports, policies, legislation and forms.'],
            ['label' => 'Report an Issue',        'icon' => 'heroicon-o-exclamation-triangle', 'url' => '/report-an-environmental-issue', 'description' => 'Tell us about pollution or environmental damage.'],
        ];

        foreach ($links as $index => $link) {
            // Keyed on label, not url: the six URLs above are placeholders
            // for routes that arrive in Specs 2-4 and will change. Labels
            // are the stable, client-facing identity of a quick link, so
            // re-running this seeder after a URL edit updates the existing
            // row instead of leaving a stale, still-active duplicate behind.
            QuickLink::updateOrCreate(
                ['label' => $link['label']],
                [...$link, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
