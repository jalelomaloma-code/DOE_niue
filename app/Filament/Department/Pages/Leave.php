<?php

namespace App\Filament\Department\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Leave extends Page
{
    protected string $view = 'filament.department.pages.leave';

    protected static ?string $slug = 'leave';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Leave';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public array $leaveDecisionStatuses = [];

    public function requests(): array
    {
        return [
            ['id' => 'LR-104', 'name' => 'Ana Vilitama', 'type' => 'Annual leave', 'team' => 'Climate Change', 'dates' => '19-21 Aug 2026', 'impact' => 'Low'],
            ['id' => 'LR-105', 'name' => 'Fina Mokoia', 'type' => 'Family leave', 'team' => 'Waste Management', 'dates' => '26-27 Aug 2026', 'impact' => 'Medium'],
            ['id' => 'LR-106', 'name' => 'Lagi Hipa', 'type' => 'Annual leave', 'team' => 'Department Support', 'dates' => '12-16 Aug 2026', 'impact' => 'Low'],
        ];
    }

    public function records(): array
    {
        return [
            ['name' => 'Lagi Hipa', 'type' => 'Annual leave', 'period' => '12-16 Aug 2026', 'days' => '5', 'annual' => '9 days', 'sick' => '8 days', 'toil' => '1.5 days', 'status' => 'Approved'],
            ['name' => 'Ana Vilitama', 'type' => 'Annual leave', 'period' => '19-21 Aug 2026', 'days' => '3', 'annual' => '11 days', 'sick' => '10 days', 'toil' => '0.5 days', 'status' => 'Pending'],
            ['name' => 'Fina Mokoia', 'type' => 'Sick leave', 'period' => '26-27 Aug 2026', 'days' => '2', 'annual' => '7 days', 'sick' => '6 days', 'toil' => '2 days', 'status' => 'Roster review'],
            ['name' => 'Tina Ikimotu', 'type' => 'TOIL', 'period' => '5 Sep 2026', 'days' => '1', 'annual' => '14 days', 'sick' => '9 days', 'toil' => '3 days', 'status' => 'Approved'],
            ['name' => 'Mika Funaki', 'type' => 'Annual leave', 'period' => '9-10 Sep 2026', 'days' => '2', 'annual' => '12 days', 'sick' => '11 days', 'toil' => '1 day', 'status' => 'Planned'],
        ];
    }

    public function balances(): array
    {
        return [
            ['name' => 'Mele Fakaiki', 'annual' => '13 days', 'sick' => '10 days', 'toil' => '0.5 days'],
            ['name' => 'Sione Tukuitonga', 'annual' => '8 days', 'sick' => '7 days', 'toil' => '2 days'],
            ['name' => 'Ana Vilitama', 'annual' => '11 days', 'sick' => '10 days', 'toil' => '0.5 days'],
            ['name' => 'Litia Talagi', 'annual' => '6 days', 'sick' => '9 days', 'toil' => '4 days'],
            ['name' => 'Pita Hekau', 'annual' => '15 days', 'sick' => '12 days', 'toil' => '0 days'],
            ['name' => 'Tina Ikimotu', 'annual' => '14 days', 'sick' => '9 days', 'toil' => '3 days'],
            ['name' => 'Ioane Fotofili', 'annual' => '10 days', 'sick' => '8 days', 'toil' => '1 day'],
            ['name' => 'Fina Mokoia', 'annual' => '7 days', 'sick' => '6 days', 'toil' => '2 days'],
            ['name' => 'Mika Funaki', 'annual' => '12 days', 'sick' => '11 days', 'toil' => '1 day'],
            ['name' => 'Lagi Hipa', 'annual' => '9 days', 'sick' => '8 days', 'toil' => '1.5 days'],
        ];
    }

    public function decide(string $id, string $status): void
    {
        $this->leaveDecisionStatuses[$id] = $status;

        Notification::make()
            ->title('Sample leave decision updated')
            ->body($id . ' marked as ' . strtolower($status) . ' for this demo session.')
            ->success()
            ->send();
    }
}
