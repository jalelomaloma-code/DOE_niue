<?php

namespace App\Filament\Department\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrStaff extends Page
{
    protected string $view = 'filament.department.pages.hr-staff';

    protected static ?string $slug = 'hr-staff';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'HR & Staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public function workers(): array
    {
        return [
            ['name' => 'Mele Fakaiki', 'role' => 'Governance Lead', 'unit' => 'Environmental Governance', 'today' => 'Office', 'status' => 'Available'],
            ['name' => 'Sione Tukuitonga', 'role' => 'Conservation Lead', 'unit' => 'Biodiversity and Conservation', 'today' => 'Field monitoring', 'status' => 'Available'],
            ['name' => 'Ana Vilitama', 'role' => 'Climate Lead', 'unit' => 'Climate Change and Ozone', 'today' => 'Reporting', 'status' => 'Available'],
            ['name' => 'Litia Talagi', 'role' => 'Waste Lead', 'unit' => 'Waste Management and Pollution Control', 'today' => 'Depot roster', 'status' => 'Available'],
            ['name' => 'Pita Hekau', 'role' => 'Environmental Officer', 'unit' => 'Environmental Governance', 'today' => 'Compliance review', 'status' => 'Available'],
            ['name' => 'Tina Ikimotu', 'role' => 'Biodiversity Officer', 'unit' => 'Biodiversity and Conservation', 'today' => 'Protected area checks', 'status' => 'Field'],
            ['name' => 'Ioane Fotofili', 'role' => 'Climate Officer', 'unit' => 'Climate Change and Ozone', 'today' => 'Adaptation project', 'status' => 'Available'],
            ['name' => 'Fina Mokoia', 'role' => 'Waste Operations Officer', 'unit' => 'Waste Management and Pollution Control', 'today' => 'Collections', 'status' => 'Field'],
            ['name' => 'Mika Funaki', 'role' => 'Community Engagement Officer', 'unit' => 'Biodiversity and Conservation', 'today' => 'School outreach', 'status' => 'Available'],
            ['name' => 'Lagi Hipa', 'role' => 'Administration Officer', 'unit' => 'Department Support', 'today' => 'Reception and records', 'status' => 'Leave'],
        ];
    }

    public function metrics(): array
    {
        return [
            ['label' => 'Available today', 'value' => '18', 'note' => 'Office, field and depot roster'],
            ['label' => 'Field assignments', 'value' => '5', 'note' => 'Monitoring, waste and outreach'],
            ['label' => 'Roster readiness', 'value' => '92%', 'note' => 'No service gap'],
            ['label' => 'Workforce risks', 'value' => '2', 'note' => 'Need Director attention'],
        ];
    }

    public function exportWorkforceSnapshot(): StreamedResponse
    {
        return response()->streamDownload(fn () => print "Niue DoE workforce snapshot - fictional sample data only.\n", 'niue-doe-workforce-snapshot.txt');
    }
}
