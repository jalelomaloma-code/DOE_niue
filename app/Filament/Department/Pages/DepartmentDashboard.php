<?php

namespace App\Filament\Department\Pages;

use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class DepartmentDashboard extends Dashboard
{
    protected string $view = 'filament.department.pages.department-dashboard';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Director overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Department Operations';

    public string $period = 'August 2026';

    /** @var array<string, string> */
    public array $approvalStatuses = [];

    public int $recordedIncidentCount = 0;

    /** @var array<string, string> */
    public array $leaveDecisionStatuses = [];

    /**
     * @return array<int, array{label: string, value: string, note: string, icon: BackedEnum}>
     */
    public function summaryCards(): array
    {
        return [
            ['label' => 'Awaiting decisions', 'value' => '3', 'note' => 'Director action required', 'icon' => Heroicon::OutlinedClock],
            ['label' => 'Active projects', 'value' => '3', 'note' => '1 currently at risk', 'icon' => Heroicon::OutlinedBellAlert],
            ['label' => 'Budget utilised', 'value' => '60%', 'note' => '$393.6K remaining', 'icon' => Heroicon::OutlinedArrowTrendingUp],
            ['label' => 'Open incidents', 'value' => '2', 'note' => '1 high priority', 'icon' => Heroicon::OutlinedTableCells],
        ];
    }

    public function approvals(): array
    {
        return [
            ['code' => 'P', 'title' => 'Coastal monitoring equipment', 'meta' => 'Purchase · Marine Unit · Due 12 Aug 2026 · $4.9K'],
            ['code' => 'T', 'title' => 'Regional climate meeting travel', 'meta' => 'Travel · Climate Change · Due 14 Aug 2026 · $3.2K'],
            ['code' => 'D', 'title' => 'Waste collection public notice', 'meta' => 'Document · Waste Management · Due 16 Aug 2026'],
        ];
    }

    public function pendingApprovalCount(): int
    {
        return collect($this->approvals())
            ->reject(fn (array $approval): bool => isset($this->approvalStatuses[$approval['title']]))
            ->count();
    }

    public function approveApproval(string $title): void
    {
        $this->approvalStatuses[$title] = 'Approved';

        Notification::make()
            ->title('Sample approval recorded')
            ->body($title . ' marked as approved for this demo session.')
            ->success()
            ->send();
    }

    public function declineApproval(string $title): void
    {
        $this->approvalStatuses[$title] = 'Declined';

        Notification::make()
            ->title('Sample approval declined')
            ->body($title . ' marked as declined for this demo session.')
            ->warning()
            ->send();
    }

    public function recordIncident(): void
    {
        $this->recordedIncidentCount++;

        Notification::make()
            ->title('Sample incident recorded')
            ->body('A fictional demonstration incident has been added to this session.')
            ->success()
            ->send();
    }

    public function downloadMonthlyReport(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo "Niue Department of Environment\n";
            echo "Monthly Director Report Preview - August 2026\n\n";
            echo "Awaiting decisions: " . $this->pendingApprovalCount() . "\n";
            echo "Active projects: 3\n";
            echo "Budget utilised: 60%\n";
            echo "Open incidents: " . (2 + $this->recordedIncidentCount) . "\n\n";
            echo "This is fictional sample data for demonstration only.\n";
        }, 'niue-doe-director-report-august-2026.txt');
    }

    public function indicators(): array
    {
        return [
            ['label' => 'Waste diversion', 'value' => '68%', 'bar' => 68, 'status' => 'Improving'],
            ['label' => 'Coastal monitoring', 'value' => '18 sites', 'bar' => 82, 'status' => 'On schedule'],
            ['label' => 'Protected area checks', 'value' => '92%', 'bar' => 92, 'status' => 'Strong'],
            ['label' => 'Public response time', 'value' => '3.4 days', 'bar' => 74, 'status' => 'Stable'],
        ];
    }

    public function programmes(): array
    {
        return [
            ['name' => 'Environmental Governance', 'projects' => 4, 'status' => 'On track', 'progress' => 76],
            ['name' => 'Biodiversity and Conservation', 'projects' => 6, 'status' => 'Monitoring', 'progress' => 68],
            ['name' => 'Climate Change and Ozone', 'projects' => 5, 'status' => 'On track', 'progress' => 72],
            ['name' => 'Waste Management and Pollution Control', 'projects' => 7, 'status' => 'Action needed', 'progress' => 59],
        ];
    }

    public function projectStatus(): array
    {
        return [
            ['label' => 'Planned', 'value' => 5, 'class' => 'bg-gray-500'],
            ['label' => 'Active', 'value' => 12, 'class' => 'bg-primary-600'],
            ['label' => 'Completed', 'value' => 3, 'class' => 'bg-success-600'],
            ['label' => 'At risk', 'value' => 2, 'class' => 'bg-warning-500'],
        ];
    }

    public function submissions(): array
    {
        return [
            ['title' => 'Illegal dumping report', 'location' => 'Alofi South', 'status' => 'Under review'],
            ['title' => 'Coastal erosion observation', 'location' => 'Hikutavake', 'status' => 'Site visit booked'],
            ['title' => 'Recycling collection enquiry', 'location' => 'Mutalau', 'status' => 'Response drafted'],
        ];
    }

    public function staffSummary(): array
    {
        return [
            ['label' => 'Available today', 'value' => '18', 'note' => 'Office, field and depot roster'],
            ['label' => 'Field assignments', 'value' => '5', 'note' => 'Monitoring, waste and outreach'],
            ['label' => 'Roster readiness', 'value' => '92%', 'note' => 'No island-wide service gap'],
            ['label' => 'Workforce risks', 'value' => '2', 'note' => 'Roles needing Director attention'],
        ];
    }

    public function staffUnits(): array
    {
        return [
            ['unit' => 'Environmental Governance', 'lead' => 'Mele Fakaiki', 'available' => '4/5', 'capacity' => 'Good', 'focus' => 'Policy reviews and compliance advice', 'risk' => 'Low'],
            ['unit' => 'Biodiversity and Conservation', 'lead' => 'Sione Tukuitonga', 'available' => '5/7', 'capacity' => 'Field work heavy', 'focus' => 'Protected area monitoring', 'risk' => 'Medium'],
            ['unit' => 'Climate Change and Ozone', 'lead' => 'Ana Vilitama', 'available' => '4/4', 'capacity' => 'Stable', 'focus' => 'Reporting and adaptation projects', 'risk' => 'Low'],
            ['unit' => 'Waste Management and Pollution Control', 'lead' => 'Litia Talagi', 'available' => '5/7', 'capacity' => 'Busy', 'focus' => 'Collections, incidents and public notices', 'risk' => 'Medium'],
        ];
    }

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

    public function workforceSignals(): array
    {
        return [
            ['label' => 'Attendance confidence', 'value' => '94%', 'trend' => 'Stable this week'],
            ['label' => 'Overtime pressure', 'value' => 'Medium', 'trend' => 'Waste team peak period'],
            ['label' => 'Training compliance', 'value' => '86%', 'trend' => '2 refreshers due'],
        ];
    }

    public function staffPriorities(): array
    {
        return [
            ['title' => 'Waste collection roster pressure', 'owner' => 'Litia Talagi and Fina Mokoia', 'status' => 'Director visibility', 'priority' => 'Medium'],
            ['title' => 'Field safety refresher completion', 'owner' => 'Sione Tukuitonga and Tina Ikimotu', 'status' => 'Due this month', 'priority' => 'Normal'],
            ['title' => 'Conservation monitoring support', 'owner' => 'Sione Tukuitonga and Mika Funaki', 'status' => 'Temporary roster support needed', 'priority' => 'Medium'],
        ];
    }

    public function leaveSummary(): array
    {
        return [
            ['label' => 'Approved leave', 'value' => '3', 'note' => 'Recorded in roster'],
            ['label' => 'Pending decisions', 'value' => (string) $this->pendingLeaveCount(), 'note' => 'Sample approvals queue'],
            ['label' => 'Roster risk', 'value' => '1', 'note' => 'Waste depot week 26 Aug'],
            ['label' => 'Leave balance alerts', 'value' => '2', 'note' => 'Planning prompts only'],
        ];
    }

    public function leaveCalendar(): array
    {
        return [
            ['period' => '12-16 Aug', 'name' => 'Lagi Hipa', 'team' => 'Department Support', 'roster' => 'Admin desk roster updated', 'status' => 'Approved', 'impact' => 'Low'],
            ['period' => '19-21 Aug', 'name' => 'Ana Vilitama', 'team' => 'Climate Change', 'roster' => 'Climate desk roster updated', 'status' => 'Pending', 'impact' => 'Low'],
            ['period' => '26-30 Aug', 'name' => 'Fina Mokoia', 'team' => 'Waste Management', 'roster' => 'Roster adjustment needed with Litia Talagi', 'status' => 'Planning', 'impact' => 'Medium'],
        ];
    }

    public function leaveRequests(): array
    {
        return [
            ['id' => 'LR-104', 'name' => 'Ana Vilitama', 'type' => 'Annual leave', 'team' => 'Climate Change', 'dates' => '19-21 Aug 2026', 'roster' => 'Climate desk roster updated', 'impact' => 'Low'],
            ['id' => 'LR-105', 'name' => 'Fina Mokoia', 'type' => 'Family leave', 'team' => 'Waste Management', 'dates' => '26-27 Aug 2026', 'roster' => 'Needs roster change with Litia Talagi', 'impact' => 'Medium'],
        ];
    }

    public function pendingLeaveCount(): int
    {
        return collect($this->leaveRequests())
            ->reject(fn (array $request): bool => isset($this->leaveDecisionStatuses[$request['id']]))
            ->count();
    }

    public function approveLeave(string $id): void
    {
        $this->leaveDecisionStatuses[$id] = 'Approved';

        Notification::make()
            ->title('Sample leave request approved')
            ->body($id . ' has been marked approved for this demo session.')
            ->success()
            ->send();
    }

    public function holdLeave(string $id): void
    {
        $this->leaveDecisionStatuses[$id] = 'Needs roster review';

        Notification::make()
            ->title('Sample leave request held')
            ->body($id . ' has been flagged for roster review.')
            ->warning()
            ->send();
    }

    public function exportWorkforceSnapshot(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo "Niue Department of Environment\n";
            echo "Director Workforce Snapshot - August 2026\n\n";
            echo "Available today: 18\n";
            echo "Roster readiness: 92%\n";
            echo "Pending leave decisions: " . $this->pendingLeaveCount() . "\n";
            echo "Workforce risks: 2\n\n";
            echo "This is fictional sample data for demonstration only.\n";
        }, 'niue-doe-workforce-snapshot-august-2026.txt');
    }

    public function deadlines(): array
    {
        return [
            ['title' => 'Quarterly waste diversion summary', 'date' => '28 Aug 2026', 'owner' => 'Waste team'],
            ['title' => 'Climate adaptation activity update', 'date' => '5 Sep 2026', 'owner' => 'Climate team'],
            ['title' => 'Biodiversity field monitoring pack', 'date' => '14 Sep 2026', 'owner' => 'Conservation team'],
        ];
    }

    public function activities(): array
    {
        return [
            'Published community recycling notice',
            'Updated coastal monitoring project status',
            'Logged public submission from Lakepa village council',
            'Uploaded draft lagoon health factsheet',
        ];
    }

    public function documents(): array
    {
        return [
            ['title' => 'Lagoon Health Factsheet', 'type' => 'Draft publication', 'date' => 'Today'],
            ['title' => 'Waste Diversion Summary', 'type' => 'Quarterly report', 'date' => 'Yesterday'],
            ['title' => 'Protected Areas Checklist', 'type' => 'Field document', 'date' => 'This week'],
        ];
    }

    public function engagement(): array
    {
        return [
            ['label' => 'Website visits', 'value' => '2,840', 'trend' => '+18% vs last month'],
            ['label' => 'Resource downloads', 'value' => '412', 'trend' => '+9%'],
            ['label' => 'Issue forms started', 'value' => '44', 'trend' => '31 completed'],
            ['label' => 'News readers', 'value' => '1,096', 'trend' => '+22%'],
        ];
    }

    public function analyticsChannels(): array
    {
        return [
            ['label' => 'Direct visits', 'value' => '1,240', 'share' => 44],
            ['label' => 'Search', 'value' => '820', 'share' => 29],
            ['label' => 'Social referrals', 'value' => '460', 'share' => 16],
            ['label' => 'Partner links', 'value' => '320', 'share' => 11],
        ];
    }

    public function topWebsiteContent(): array
    {
        return [
            ['title' => 'Waste Management and Pollution Control', 'views' => '684', 'action' => 'Keep on homepage'],
            ['title' => 'Report an Environmental Issue', 'views' => '512', 'action' => 'Monitor form completion'],
            ['title' => 'Climate Change and Ozone', 'views' => '408', 'action' => 'Add latest project update'],
            ['title' => 'Lagoon Health Factsheet', 'views' => '296', 'action' => 'Promote in resources'],
        ];
    }

    public function digitalServiceHealth(): array
    {
        return [
            ['label' => 'Mobile users', 'value' => '71%', 'status' => 'Strong'],
            ['label' => 'Average load time', 'value' => '1.8s', 'status' => 'Good'],
            ['label' => 'Form completion', 'value' => '70%', 'status' => 'Watch'],
        ];
    }

    public function exportAnalyticsSnapshot(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo "Niue Department of Environment\n";
            echo "Website Analytics Snapshot - August 2026\n\n";
            echo "Website visits: 2,840\n";
            echo "Resource downloads: 412\n";
            echo "Issue forms started: 44\n";
            echo "News readers: 1,096\n\n";
            echo "Top page: Waste Management and Pollution Control\n";
            echo "This is fictional sample data for demonstration only.\n";
        }, 'niue-doe-website-analytics-august-2026.txt');
    }
}
