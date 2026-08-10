<?php

namespace App\Filament\Department\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Overview extends Page
{
    protected string $view = 'filament.department.pages.overview';

    protected static ?string $slug = 'overview';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public function metrics(): array
    {
        return [
            ['label' => 'Active programmes', 'value' => '4', 'note' => 'All branches reporting'],
            ['label' => 'Active projects', 'value' => '22', 'note' => '3 need Director attention'],
            ['label' => 'Open incidents', 'value' => '9', 'note' => '2 high priority'],
            ['label' => 'Reports due', 'value' => '5', 'note' => 'Next 30 days'],
        ];
    }

    public function programmeStatus(): array
    {
        return [
            ['name' => 'Environmental Governance', 'status' => 'On track', 'progress' => 76],
            ['name' => 'Biodiversity and Conservation', 'status' => 'Monitoring', 'progress' => 68],
            ['name' => 'Climate Change and Ozone', 'status' => 'On track', 'progress' => 72],
            ['name' => 'Waste Management and Pollution Control', 'status' => 'Action needed', 'progress' => 59],
        ];
    }
}
