<?php

namespace Database\Seeders;

use App\Models\HomepageSetting;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::current()->update([
            'department_name' => 'Niue Department of Environment',
            'government_name' => 'Government of Niue',
            'address' => 'Alofi, Niue',
            'email' => 'environment@mail.gov.nu',
            'office_hours' => 'Monday to Friday, 8:00am – 4:00pm',
            'footer_text' => 'Protecting Niue\'s natural environment for future generations.',
        ]);

        HomepageSetting::current()->update([
            'hero_headline' => "Protecting Niue's Environment for Future Generations",
            'hero_intro' => 'Supporting conservation, biodiversity, sustainable waste management and responsible stewardship of Niue\'s natural environment.',
            'hero_primary_cta_label' => 'Explore Our Work',
            'hero_primary_cta_url' => '/environment-programmes',
            'hero_secondary_cta_label' => 'Latest News',
            'hero_secondary_cta_url' => '/news',
            'quick_links_heading' => 'Quick Links',
            'programmes_heading' => 'Our Environment Programmes',
            'programmes_intro' => 'The Department delivers programmes across conservation, waste, climate and biodiversity.',
            'news_heading' => 'Latest News',
            'projects_heading' => 'Featured Projects',
            'resources_heading' => 'Publications and Resources',
            'report_heading' => 'Report an Environmental Issue',
            'report_intro' => 'Help us protect Niue\'s environment. Report pollution, illegal dumping or damage to protected areas.',
            'report_cta_label' => 'Report an Issue',
            'report_cta_url' => '/report-an-environmental-issue',
        ]);
    }
}
