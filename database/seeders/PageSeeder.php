<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\DocumentCategory;
use App\Models\Page;
use App\Support\DemoImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Populates the full information architecture with demo pages so every
 * navigation item, and every child of About Us and Our Work, resolves to a
 * real page instead of a 404.
 *
 * Hierarchy regrouped 2026-08-08 (spec §3a): six top-level pages. The four
 * topic sections that used to be top-level items are now children of a new
 * "Our Work" landing page, alongside a "Projects" card that doesn't resolve
 * yet -- Projects, News & Events and Resources belong to a later spec and
 * are deliberately not created here (see the brief).
 *
 * Every record is `is_demo => true`, `status => Published`, and
 * `published_at` in the past, and is removed (with any attached image) by
 * `php artisan demo:purge`.
 *
 * Copy is generic environmental language: no real Niue institution names,
 * statistics, dates or events, so nothing here can be mistaken for an
 * authoritative claim before real Department content replaces it.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $about = $this->createPage([
            'title' => 'About Us',
            'slug' => 'about',
            'intro' => 'Learn about the Department, its mandate and the people behind its work.',
            'content' => [
                $this->richText(
                    'What we do',
                    '<p>The Department of Environment leads the government\'s work on environmental protection, conservation and sustainable resource management. We work alongside communities, landowners and other government agencies to look after the natural environment for current and future generations.</p><p>This section introduces the Department, the mandate it operates under, and the team responsible for delivering its programmes.</p>'
                ),
            ],
        ]);

        $this->createPage([
            'title' => 'About the Department',
            'slug' => 'about-the-department',
            'parent_id' => $about->id,
            'intro' => 'An overview of the Department\'s role and areas of responsibility.',
            'content' => [
                $this->richText(
                    null,
                    '<p>The Department of Environment is responsible for environmental policy, conservation programmes, waste management and climate resilience work. Our staff coordinate closely with village councils, landowners and regional partners to deliver programmes that protect natural habitats and support sustainable use of resources.</p><p>Our work spans forest and coastal conservation, marine protection, waste and recycling services, and climate adaptation planning.</p>'
                ),
            ],
        ]);

        $this->createPage([
            'title' => 'Mandate',
            'slug' => 'mandate',
            'parent_id' => $about->id,
            'intro' => 'The legal and policy basis for the Department\'s work.',
            'content' => [
                $this->richText(
                    null,
                    '<p>The Department operates under environmental legislation and government policy that sets out its responsibilities for protecting the natural environment, managing waste, and supporting climate resilience. This mandate guides the programmes and services described elsewhere on this site.</p><p>Detailed legislation and policy documents are published in the resources section as they become available.</p>'
                ),
            ],
        ]);

        $this->createPage([
            'title' => 'Mission & Vision',
            'slug' => 'mission-and-vision',
            'parent_id' => $about->id,
            'intro' => 'What the Department is working towards, and why.',
            'content' => [
                $this->richText(
                    'Our mission',
                    '<p>To protect and sustainably manage the natural environment, working in partnership with communities to safeguard the land, coastline and waters that support island life.</p>'
                ),
                $this->richText(
                    'Our vision',
                    '<p>A resilient environment, cared for by an informed and engaged community, where conservation and sustainable development go hand in hand.</p>'
                ),
            ],
        ]);

        $this->createPage([
            'title' => 'Our Team',
            'slug' => 'our-team',
            'parent_id' => $about->id,
            'intro' => 'The people who lead and deliver the Department\'s programmes.',
            'content' => [
                $this->block('team_grid', ['heading' => 'Meet the team']),
            ],
        ]);

        $ourWork = $this->createPage([
            'title' => 'Our Work',
            'slug' => 'our-work',
            'intro' => 'Conservation, waste, climate and biodiversity programmes, and the projects that deliver them.',
            'content' => [
                $this->richText(
                    null,
                    '<p>The Department\'s work is organised around a small number of ongoing programme areas, each covering a distinct part of the environment. Browse each section below to read about current activity, or explore individual projects delivered under these programmes.</p>'
                ),
                $this->block('card_grid', [
                    'heading' => 'Our work areas',
                    'cards' => [
                        ['title' => 'Environment Programmes', 'text' => 'An overview of every current Department programme.', 'url' => '/our-work/environment-programmes'],
                        ['title' => 'Waste & Recycling', 'text' => 'Waste services, recycling and disposal guidance.', 'url' => '/our-work/waste-and-recycling'],
                        ['title' => 'Biodiversity & Conservation', 'text' => 'Protecting native species and habitats.', 'url' => '/our-work/biodiversity-and-conservation'],
                        ['title' => 'Climate & Marine', 'text' => 'Climate resilience and marine protection.', 'url' => '/our-work/climate-and-marine'],
                        // Projects lands here in a later spec; this card is
                        // intentionally left pointing at a path that does not
                        // resolve yet -- see the brief and the QuickLinkSeeder
                        // note on the same subject.
                        ['title' => 'Projects', 'text' => 'Individual projects delivered under our programmes.', 'url' => '/our-work/projects'],
                    ],
                ]),
            ],
        ]);

        $this->createPage([
            'title' => 'Environment Programmes',
            'slug' => 'environment-programmes',
            'parent_id' => $ourWork->id,
            'intro' => 'An overview of every current Department programme.',
            'content' => [
                $this->richText(
                    null,
                    '<p>The Department runs a small number of ongoing programmes covering conservation, waste management, marine protection, biodiversity and climate resilience. Each programme below coordinates activity across the island in partnership with communities and other government agencies.</p>'
                ),
                $this->block('programmes_list', ['heading' => 'Current programmes']),
            ],
        ]);

        $this->createPage([
            'title' => 'Waste & Recycling',
            'slug' => 'waste-and-recycling',
            'parent_id' => $ourWork->id,
            'intro' => 'Waste services, recycling and disposal guidance.',
            'content' => [
                $this->richText(
                    null,
                    '<p>The Department coordinates household and commercial waste collection, a recycling stream for common materials, and safe handling guidance for hazardous items. The services below outline what is available and how to use them.</p>'
                ),
                $this->block('card_grid', [
                    'heading' => 'Services',
                    'cards' => [
                        ['title' => 'Household collection', 'text' => 'Regular collection of household waste from villages across the island.', 'url' => null],
                        ['title' => 'Recycling drop-off', 'text' => 'Designated points for recyclable materials.', 'url' => null],
                        ['title' => 'Hazardous waste guidance', 'text' => 'Safe handling and disposal of batteries, oil and other hazardous items.', 'url' => null],
                    ],
                ]),
                $this->block('documents_list', [
                    'heading' => 'Forms',
                    'category_id' => DocumentCategory::where('slug', 'forms')->value('id'),
                ]),
            ],
        ]);

        $this->createPage([
            'title' => 'Biodiversity & Conservation',
            'slug' => 'biodiversity-and-conservation',
            'parent_id' => $ourWork->id,
            'intro' => 'Protecting native species and habitats.',
            'content' => [
                $this->richText(
                    null,
                    '<p>Understanding and protecting native species is at the heart of the Department\'s conservation work. This includes habitat surveys, control of invasive species, and community-led restoration of forest and coastal vegetation.</p>'
                ),
                $this->block('image', [
                    'url' => $this->demoImageUrl('forest', 'DEMO - biodiversity and conservation', 1600, 900),
                    'alt' => 'Placeholder image representing biodiversity and conservation work (demo content)',
                    'caption' => 'Placeholder image standing in for real Department photography.',
                ]),
            ],
        ]);

        $this->createPage([
            'title' => 'Climate & Marine',
            'slug' => 'climate-and-marine',
            'parent_id' => $ourWork->id,
            'intro' => 'Climate resilience and marine protection.',
            'content' => [
                $this->richText(
                    null,
                    '<p>As a small island community, we face particular exposure to climate-related risks. This programme area supports climate adaptation planning and community resilience, alongside ongoing work to protect reef systems and marine habitats.</p>'
                ),
                $this->block('callout', [
                    'heading' => 'Reporting a marine or coastal concern',
                    'body' => 'If you notice unusual coastal erosion, reef damage or a marine environmental concern, please get in touch with the Department using the contact details on this site.',
                    'tone' => 'info',
                ]),
            ],
        ]);

        $this->createPage([
            'title' => 'Contact Us',
            'slug' => 'contact',
            'intro' => 'Get in touch with the Department of Environment.',
            'content' => [
                $this->richText(
                    null,
                    '<p>We welcome enquiries about our programmes, services and how to get involved. Use the details below to reach the Department, or visit during office hours.</p>'
                ),
                $this->block('contact_details', ['heading' => 'Contact details']),
            ],
        ]);

        $this->createPage([
            'title' => 'Privacy',
            'slug' => 'privacy',
            'intro' => 'How the Department handles personal information.',
            'show_in_section_nav' => false,
            'content' => [
                $this->richText(
                    null,
                    '<p>This placeholder privacy notice describes, in general terms, how information submitted through this website is handled. It is demo content and will be replaced with the Department\'s actual privacy policy before launch.</p><p>Any personal information collected through this site is used only for the purpose it was provided and is not shared outside the Department except where required by law.</p>'
                ),
            ],
        ]);

        $this->createPage([
            'title' => 'Terms',
            'slug' => 'terms',
            'intro' => 'Terms of use for this website.',
            'show_in_section_nav' => false,
            'content' => [
                $this->richText(
                    null,
                    '<p>This placeholder terms of use page describes, in general terms, the conditions for using this website. It is demo content and will be replaced with the Department\'s actual terms before launch.</p>'
                ),
            ],
        ]);

        $this->createPage([
            'title' => 'Accessibility',
            'slug' => 'accessibility',
            'intro' => 'Our commitment to an accessible website.',
            'show_in_section_nav' => false,
            'content' => [
                $this->richText(
                    null,
                    '<p>The Department is committed to making this website usable by as many people as possible, including people using assistive technology. This placeholder page will be replaced with a full accessibility statement before launch.</p>'
                ),
            ],
        ]);
    }

    /**
     * @param  array{title: string, slug: string, intro?: string, content?: array, parent_id?: int|null, show_in_section_nav?: bool}  $attributes
     */
    private function createPage(array $attributes): Page
    {
        return Page::create([
            'title' => $attributes['title'],
            'slug' => $attributes['slug'],
            'parent_id' => $attributes['parent_id'] ?? null,
            'intro' => $attributes['intro'] ?? null,
            'content' => $attributes['content'] ?? [],
            'sort_order' => 0,
            'show_in_section_nav' => $attributes['show_in_section_nav'] ?? true,
            'status' => ContentStatus::Published,
            'published_at' => now()->subWeek(),
            'is_demo' => true,
        ]);
    }

    /** @return array{type: string, data: array} */
    private function richText(?string $heading, string $body): array
    {
        return $this->block('rich_text', array_filter([
            'heading' => $heading,
            'body' => $body,
        ], fn ($value) => $value !== null));
    }

    /** @return array{type: string, data: array} */
    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data];
    }

    /**
     * Generates a deterministic placeholder image via DemoImageGenerator and
     * copies it onto the 'public' disk under 'page-images' -- the same disk
     * and directory the image block's FileUpload field uses in the admin
     * (see PageForm) -- so the resulting URL is servable through the
     * `public/storage` symlink exactly like an editor-uploaded image.
     */
    private function demoImageUrl(string $palette, string $label, int $width, int $height): string
    {
        $sourcePath = DemoImageGenerator::make($palette, $label, $width, $height);
        $filename = basename($sourcePath);

        $directory = storage_path('app/public/page-images');
        File::ensureDirectoryExists($directory);

        $destination = $directory.DIRECTORY_SEPARATOR.$filename;

        if (! file_exists($destination)) {
            File::copy($sourcePath, $destination);
        }

        return '/storage/page-images/'.$filename;
    }
}
