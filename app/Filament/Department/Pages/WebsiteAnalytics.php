<?php

namespace App\Filament\Department\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteAnalytics extends Page
{
    protected string $view = 'filament.department.pages.website-analytics';

    protected static ?string $slug = 'website-analytics';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Website Analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

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

    public function referrers(): array
    {
        return [
            ['source' => 'gov.nu', 'type' => 'Government portal', 'visits' => '286', 'note' => 'Strong public service pathway'],
            ['source' => 'facebook.com', 'type' => 'Social media', 'visits' => '244', 'note' => 'Waste notices performing well'],
            ['source' => 'sprep.org', 'type' => 'Regional partner', 'visits' => '118', 'note' => 'Climate and biodiversity referrals'],
            ['source' => 'google.com', 'type' => 'Search', 'visits' => '820', 'note' => 'High demand for reporting and waste pages'],
            ['source' => 'mecc.gov.fj', 'type' => 'Pacific ministry reference', 'visits' => '63', 'note' => 'Useful regional audience signal'],
        ];
    }

    public function referrerActions(): array
    {
        return [
            'Keep report-an-issue links prominent on government portal pathways.',
            'Share waste collection updates consistently through social posts.',
            'Cross-link climate resources with regional partner pages.',
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
            echo "Top referrer: google.com\n";
            echo "This is fictional sample data for demonstration only.\n";
        }, 'niue-doe-website-analytics-august-2026.txt');
    }
}
