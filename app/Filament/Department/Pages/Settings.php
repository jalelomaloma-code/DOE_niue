<?php

namespace App\Filament\Department\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Settings extends Page
{
    protected string $view = 'filament.department.pages.settings';

    protected static ?string $slug = 'settings';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public function settings(): array
    {
        return [
            ['label' => 'Director view mode', 'value' => 'Enabled for demonstration'],
            ['label' => 'Sample data mode', 'value' => 'On'],
            ['label' => 'Operational integrations', 'value' => 'Preview only'],
            ['label' => 'CMS separation', 'value' => 'Website Management remains under /admin'],
        ];
    }
}
