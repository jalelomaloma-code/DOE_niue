<?php

namespace App\Filament\Department\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FullDashboardPreview extends Page
{
    protected string $view = 'filament.department.pages.full-dashboard-preview';

    protected static ?string $slug = 'full-preview';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Full Department Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    public static function canAccess(): bool
    {
        return auth()->user()?->mayPublish() ?? false;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Fictional sample data for Director-level demonstration only.';
    }

    public function executiveMetrics(): array
    {
        return [
            ['label' => 'Portfolio health', 'value' => 'Stable', 'note' => '3 areas need attention'],
            ['label' => 'Pending approvals', 'value' => '7', 'note' => '2 high priority'],
            ['label' => 'Budget utilisation', 'value' => '67%', 'note' => 'Across sample projects'],
            ['label' => 'Open compliance items', 'value' => '5', 'note' => 'Next 45 days'],
        ];
    }

    public function approvals(): array
    {
        return [
            ['title' => 'Community clean-up funding note', 'owner' => 'Programme lead', 'status' => 'Director review'],
            ['title' => 'Monitoring equipment purchase', 'owner' => 'Operations', 'status' => 'Finance check'],
            ['title' => 'Draft public advisory', 'owner' => 'Communications', 'status' => 'Legal check'],
        ];
    }

    public function staffOverview(): array
    {
        return [
            ['label' => 'Present today', 'value' => '18'],
            ['label' => 'Approved leave', 'value' => '3'],
            ['label' => 'Field assignments', 'value' => '5'],
        ];
    }

    public function budgets(): array
    {
        return [
            ['programme' => 'Waste Management', 'budget' => '$185k', 'spent' => '$112k', 'variance' => '39% remaining'],
            ['programme' => 'Climate Change', 'budget' => '$240k', 'spent' => '$171k', 'variance' => '29% remaining'],
            ['programme' => 'Biodiversity', 'budget' => '$160k', 'spent' => '$102k', 'variance' => '36% remaining'],
        ];
    }

    public function milestones(): array
    {
        return [
            ['title' => 'Village recycling rollout', 'risk' => 'Medium', 'next' => 'Confirm collection calendar'],
            ['title' => 'Coastal monitoring survey', 'risk' => 'Low', 'next' => 'Finalise data entry'],
            ['title' => 'Ozone reporting pack', 'risk' => 'Medium', 'next' => 'Await supplier declaration'],
        ];
    }

    public function compliance(): array
    {
        return [
            ['title' => 'Permit register review', 'date' => '18 Aug 2026', 'status' => 'Scheduled'],
            ['title' => 'Hazardous waste return', 'date' => '30 Aug 2026', 'status' => 'Drafting'],
            ['title' => 'Regional reporting submission', 'date' => '12 Sep 2026', 'status' => 'On track'],
        ];
    }

    public function incidents(): array
    {
        return [
            ['id' => 'ENV-026', 'type' => 'Pollution complaint', 'priority' => 'High', 'status' => 'Investigation'],
            ['id' => 'ENV-027', 'type' => 'Waste collection request', 'priority' => 'Normal', 'status' => 'Assigned'],
            ['id' => 'ENV-028', 'type' => 'Protected species sighting', 'priority' => 'Normal', 'status' => 'Logged'],
        ];
    }

    public function community(): array
    {
        return [
            ['activity' => 'School environment week session', 'location' => 'Alofi', 'reach' => '120 people'],
            ['activity' => 'Village recycling briefing', 'location' => 'Hakupu', 'reach' => '42 people'],
            ['activity' => 'Public survey responses', 'location' => 'Island-wide', 'reach' => '86 submissions'],
        ];
    }

    public function previewMonthlyReport(): StreamedResponse
    {
        Notification::make()
            ->title('Monthly report preview generated')
            ->success()
            ->send();

        return response()->streamDownload(function (): void {
            echo "Niue Department of Environment\n";
            echo "Full Department Dashboard - Optional Upgrade Preview\n";
            echo "Monthly Director Report Preview - August 2026\n\n";
            echo "Portfolio health: Stable\n";
            echo "Pending approvals: 7\n";
            echo "Budget utilisation: 67%\n";
            echo "Open compliance items: 5\n\n";
            echo "This is fictional sample data for demonstration only.\n";
        }, 'niue-doe-full-dashboard-preview-august-2026.txt');
    }
}
