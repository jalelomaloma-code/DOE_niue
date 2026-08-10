<?php

namespace App\Support;

use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;

class DemoImages
{
    public static function for(mixed $record): string
    {
        $text = strtolower(implode(' ', array_filter([
            $record->title ?? null,
            $record->summary ?? null,
            $record instanceof Project ? $record->programme?->title : null,
            $record instanceof NewsArticle ? $record->category?->name : null,
        ])));

        if (str_contains($text, 'waste') || str_contains($text, 'pollution') || str_contains($text, 'recycling')) {
            return asset('images/demo/waste-pollution.png');
        }

        if (str_contains($text, 'climate') || str_contains($text, 'marine') || str_contains($text, 'reef') || str_contains($text, 'ozone')) {
            return asset('images/demo/climate-change-ozone.png');
        }

        if (str_contains($text, 'biodiversity') || str_contains($text, 'conservation') || str_contains($text, 'forest')) {
            return asset('images/demo/biodiversity-conservation.png');
        }

        if ($record instanceof Programme || $record instanceof Project || $record instanceof NewsArticle) {
            return asset('images/demo/environmental-governance.png');
        }

        return asset('images/demo/homepage-hero.png');
    }

    public static function cardFor(mixed $record): string
    {
        $text = strtolower(implode(' ', array_filter([
            $record->title ?? null,
            $record->summary ?? null,
            $record instanceof Project ? $record->programme?->title : null,
            $record instanceof NewsArticle ? $record->category?->name : null,
        ])));

        if ($record instanceof Project) {
            return asset('images/demo/cards/projects-card.png');
        }

        if (str_contains($text, 'waste') || str_contains($text, 'pollution') || str_contains($text, 'recycling')) {
            return asset('images/demo/cards/waste-card.png');
        }

        if (str_contains($text, 'climate')) {
            return asset('images/demo/cards/climate-card.png');
        }

        if (str_contains($text, 'marine') || str_contains($text, 'reef')) {
            return asset('images/demo/cards/marine-card.png');
        }

        if (str_contains($text, 'biodiversity') || str_contains($text, 'conservation') || str_contains($text, 'forest')) {
            return asset('images/demo/cards/biodiversity-card.png');
        }

        return asset('images/demo/cards/governance-card.png');
    }

    public static function branch(string $slug): string
    {
        return match ($slug) {
            'environmental-governance' => asset('images/demo/environmental-governance.png'),
            'biodiversity-conservation' => asset('images/demo/biodiversity-conservation.png'),
            'climate-change-ozone' => asset('images/demo/climate-change-ozone.png'),
            'waste-pollution' => asset('images/demo/waste-pollution.png'),
            default => asset('images/demo/homepage-hero.png'),
        };
    }
}
