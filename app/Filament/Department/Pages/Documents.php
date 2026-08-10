<?php

namespace App\Filament\Department\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Documents extends Page
{
    protected string $view = 'filament.department.pages.documents';

    protected static ?string $slug = 'documents';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Documents';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public function documents(): array
    {
        return [
            ['title' => 'Lagoon Health Factsheet', 'type' => 'Draft publication', 'owner' => 'Mika Funaki', 'status' => 'Draft'],
            ['title' => 'Waste Diversion Summary', 'type' => 'Quarterly report', 'owner' => 'Litia Talagi', 'status' => 'Ready'],
            ['title' => 'Protected Areas Checklist', 'type' => 'Field document', 'owner' => 'Tina Ikimotu', 'status' => 'In review'],
            ['title' => 'Climate Adaptation Update', 'type' => 'Monthly report', 'owner' => 'Ana Vilitama', 'status' => 'Ready'],
        ];
    }
}
