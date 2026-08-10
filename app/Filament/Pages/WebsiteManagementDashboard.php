<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class WebsiteManagementDashboard extends Dashboard
{
    protected static bool $isDiscovered = false;

    protected static ?string $title = 'Website Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Website Management';

    public static function getNavigationLabel(): string
    {
        return 'Website Management';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Content-management area for public website pages, news, documents, navigation and homepage sections.';
    }
}
