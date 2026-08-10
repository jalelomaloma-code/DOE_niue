<?php

namespace App\Http\Controllers;

use App\Support\DemoImages;
use Illuminate\Contracts\View\View;

class BranchController extends Controller
{
    public function environmentalGovernance(): View
    {
        return view('branches.show', [
            'title' => 'Environmental Governance',
            'intro' => 'Policy, assessment, compliance and coordination work that supports responsible environmental decision-making.',
            'image' => DemoImages::branch('environmental-governance'),
            'items' => [
                'Environmental policy and planning',
                'Environmental assessment guidance',
                'Compliance coordination',
                'Partnerships with communities, agencies and regional bodies',
            ],
        ]);
    }

    public function climateChangeAndOzone(): View
    {
        return view('branches.show', [
            'title' => 'Climate Change and Ozone',
            'intro' => 'Climate resilience, adaptation planning and ozone-related responsibilities for Niue.',
            'image' => DemoImages::branch('climate-change-ozone'),
            'items' => [
                'Climate adaptation and resilience planning',
                'Marine and coastal climate risk coordination',
                'Ozone-depleting substances awareness',
                'Regional climate reporting and partnerships',
            ],
        ]);
    }

    public function wasteManagementAndPollutionControl(): View
    {
        return view('branches.waste-management', [
            'title' => 'Waste Management and Pollution Control',
            'intro' => 'Waste collection, recycling, landfill guidance and pollution-control services for households, businesses, schools and public areas.',
            'image' => DemoImages::branch('waste-pollution'),
            'contact' => [
                'email' => 'info@wastemanagementniue.nu',
                'phone' => '+683 4159',
                'hours' => '8:00am - 4:00pm',
                'address' => 'Fonuakula, Alofi',
            ],
        ]);
    }
}
