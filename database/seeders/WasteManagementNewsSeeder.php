<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use Illuminate\Database\Seeder;

class WasteManagementNewsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->posts() as $index => $data) {
            $category = NewsCategory::firstOrCreate(
                ['slug' => str($data['category'])->slug()->toString()],
                ['name' => $data['category'], 'sort_order' => 0],
            );

            $body = collect($data['paragraphs'])
                ->map(fn (string $paragraph): string => "<p>{$paragraph}</p>")
                ->implode('');

            $body .= '<p><strong>Source:</strong> <a href="'.$data['source_url'].'">Waste Management Niue, originally published '.$data['source_date_label'].'</a>.</p>';

            $article = NewsArticle::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'body' => $body,
                    'news_category_id' => $category->id,
                    'author_name' => 'Waste Management Niue',
                    'is_featured' => $index < 4,
                    'status' => ContentStatus::Published,
                    'published_at' => $data['published_at'],
                    'share_to_facebook' => false,
                    'is_demo' => false,
                ],
            );

            $this->attachFeaturedImage($article, $data);
            $this->attachGalleryImages($article, $data);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function attachFeaturedImage(NewsArticle $article, array $data): void
    {
        if ($article->hasMedia('featured_image')) {
            return;
        }

        $article->addMedia(public_path($data['image']))
            ->preservingOriginal()
            ->withCustomProperties([
                'alt' => $data['image_alt'],
                'source_url' => $data['source_url'],
            ])
            ->toMediaCollection('featured_image');
    }

    /** @param  array<string, mixed>  $data */
    private function attachGalleryImages(NewsArticle $article, array $data): void
    {
        foreach ($data['gallery'] ?? [] as $image) {
            $fileName = basename($image['path']);

            if ($article->getMedia('article_images')->contains('file_name', $fileName)) {
                continue;
            }

            $article->addMedia(public_path($image['path']))
                ->preservingOriginal()
                ->withCustomProperties([
                    'alt' => $image['alt'],
                    'source_url' => $data['source_url'],
                ])
                ->toMediaCollection('article_images', 'public');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function posts(): array
    {
        return [
            [
                'slug' => 'niue-waste-project-supports-primary-and-secondary-schools',
                'title' => 'Niue Waste Project supports primary and secondary schools',
                'excerpt' => 'Waste Management Niue supplied tools to support technology and handcraft learning at Niue Primary School and Niue High School.',
                'paragraphs' => [
                    'The Niue Waste Management Project, under the Project Management Coordination Unit, supplied hand tools and power tools to Niue Primary School and Niue High School for use in technology and handcraft classes.',
                    'The partnership was designed to involve students in practical recycling activities, including creating signs from recovered materials for the Resource Recycling Centre. The project also aimed to encourage creativity and help students build useful technical skills.',
                ],
                'category' => 'Community',
                'published_at' => '2022-09-30 09:00:00',
                'source_date_label' => '30 September 2022',
                'source_url' => 'https://niuewastemanagement.nu/2022/09/30/niue-waste-project-providing-support-to-both-primary-secondary-schools-in-niue/',
                'image' => 'images/news/waste-management-schools/school-tools-1.jpg',
                'image_alt' => 'Waste Management Niue representatives with tools supplied for school technology classes',
                'gallery' => [
                    [
                        'path' => 'images/news/waste-management-schools/school-tools-2.jpg',
                        'alt' => 'Niue school students and project representatives standing with supplied tools',
                    ],
                ],
            ],
            [
                'slug' => 'niue-waste-project-launched-its-new-website-local-made-animation-at-niue-primary-school',
                'title' => 'Niue Waste Project launches website and locally made animation',
                'excerpt' => 'A two-day school event introduced waste-management activities, the project website and a locally produced educational animation.',
                'paragraphs' => [
                    'The Niue Waste Management Project hosted early childhood and Niue Primary School students for two days of practical waste-management activities. The event included the launch of the project website and a locally produced animation.',
                    'The website explains the island\'s landfill and recycling services. The animation introduces the project mascot, Lulu Vale, and encourages households to separate waste before taking it to a landfill or recycling facility.',
                ],
                'category' => 'Community',
                'published_at' => '2022-08-26 09:00:00',
                'source_date_label' => '26 August 2022',
                'source_url' => 'https://niuewastemanagement.nu/2022/08/26/niue-waste-project-launched-its-new-website-local-made-animation-at-niue-primary-school/',
                'image' => 'images/news/waste-management-archive/website-animation.jpg',
                'image_alt' => 'Niue students and project representatives at the waste-management website and animation launch',
            ],
            [
                'slug' => 'ma-e-anoiha-niue-waste-management-project-song',
                'title' => 'Ma e Anoiha - Niue Waste Management Project song',
                'excerpt' => 'Local artists collaborated on Ma e Anoiha, a song promoting responsible waste-management practices for Niue\'s future.',
                'paragraphs' => [
                    'Ma e Anoiha, meaning For the Future, was created to build awareness of sound waste-management practices in Niue. Gabi Tuhipa performs the song with Lavea Puheke, with local production by Rocksteady Entertainment under the Niue Waste Management Project.',
                    'The song was released through Radio Sunshine and project media channels with an <a href="https://www.youtube.com/watch?v=HFccCJdd5is">official music video</a>. The project acknowledged support from the Australian Government and the Government of Niue.',
                ],
                'category' => 'Media Releases',
                'published_at' => '2022-08-15 12:00:00',
                'source_date_label' => '15 August 2022',
                'source_url' => 'https://niuewastemanagement.nu/2022/08/15/ma-e-anoiha-niue-waste-management-project-song/',
                'image' => 'images/news/waste-management-archive/ma-e-anoiha-song.jpg',
                'image_alt' => 'Ma e Anoiha music video artwork featuring the song performer',
            ],
            [
                'slug' => 'niue-waste-management-project-donated-rubbish-bins-to-the-niue-high-school',
                'title' => 'Waste Management Project donates recycling bins to Niue High School',
                'excerpt' => 'Ten recycling systems comprising 44 bins were supplied to Niue High School to support responsible sorting and disposal.',
                'paragraphs' => [
                    'The Niue Waste Management Project donated ten recycling systems, comprising 44 bins, to Niue High School. The bins were installed around the school to make waste separation more accessible to students and staff.',
                    'The initiative was funded by Australian Aid in partnership with the Government of Niue. The project encouraged students and visitors to use the correct waste stream and help keep the school grounds clean.',
                ],
                'category' => 'Community',
                'published_at' => '2022-08-15 11:00:00',
                'source_date_label' => '15 August 2022',
                'source_url' => 'https://niuewastemanagement.nu/2022/08/15/niue-waste-management-project-donated-rubbish-bins-to-the-niue-high-school/',
                'image' => 'images/news/waste-management-archive/high-school-recycling-bins.jpg',
                'image_alt' => 'Waste Management Project representatives presenting recycling bins at Niue High School',
            ],
            [
                'slug' => 'official-blessing-of-the-niue-waste-project-site-hikufenoga-tamakautoga',
                'title' => 'Official blessing of the Niue Resource Recycling Centre site',
                'excerpt' => 'Partners and community representatives gathered at Hikufenoga, Tamakautoga, to bless the planned recycling-centre site.',
                'paragraphs' => [
                    'The Niue Waste Management Project held a blessing at the Hikufenoga site for the new Niue Resource Recycling Centre on 20 August 2021.',
                    'The event brought together donors, government representatives, neighbouring residents, village representatives, contractors and agencies involved in constructing the centre.',
                ],
                'category' => 'Environment Updates',
                'published_at' => '2022-08-15 10:00:00',
                'source_date_label' => '15 August 2022',
                'source_url' => 'https://niuewastemanagement.nu/2022/08/15/official-blessing-of-the-niue-waste-project-site-hikufenoga-tamakautoga/',
                'image' => 'images/news/waste-management-archive/recycling-centre-blessing.jpg',
                'image_alt' => 'Project representatives holding the Niue Resource Recycling Centre site plan at Hikufenoga',
            ],
            [
                'slug' => 'niue-waste-management-project-launched-1st-of-4-episodes-of-lalaga-podcast-2',
                'title' => 'Waste Management Project launches Lalaga Podcast',
                'excerpt' => 'The first Lalaga Podcast episode connected family, health, social media and waste management for a better Niue.',
                'paragraphs' => [
                    'The Niue Waste Management Project launched the first of four Lalaga Podcast episodes through the Broadcasting Corporation of Niue.',
                    'Under the theme of weaving lifestyle and environment together for a better Niue, the first episode featured project staff discussing family, health, social media and waste management.',
                ],
                'category' => 'Media Releases',
                'published_at' => '2022-08-14 09:00:00',
                'source_date_label' => '14 August 2022',
                'source_url' => 'https://niuewastemanagement.nu/2022/08/14/niue-waste-management-project-launched-1st-of-4-episodes-of-lalaga-podcast-2/',
                'image' => 'images/news/waste-management-archive/lalaga-podcast.png',
                'image_alt' => 'Lalaga Podcast artwork with the theme Weaving our Lifestyle and Environment Together for a Better Niue',
            ],
            [
                'slug' => 'new-vehicle-for-niuean-invasive-species-battler',
                'title' => 'New vehicle supports Niuean invasive-species programmes',
                'excerpt' => 'A purpose-selected vehicle was launched to support feral-pig management and invasive-weed control across Niue.',
                'paragraphs' => [
                    'Department Director and Global Environment Facility Operational Focal Point Haden Talagi launched a vehicle for the Niuean Invasive Species Battler initiative.',
                    'Provided through the GEF-6 Regional Invasives Project, the vehicle supports feral-pig management and invasive-weed spraying. The regional project links Niue with Tuvalu, the Marshall Islands and Tonga.',
                ],
                'category' => 'Environment Updates',
                'published_at' => '2021-02-19 12:00:00',
                'source_date_label' => '19 February 2021',
                'source_url' => 'https://niuewastemanagement.nu/2021/02/19/new-vehicle-for-niuean-invasive-species-battler/',
                'image' => 'images/news/waste-management-archive/invasive-species-vehicle.jpg',
                'image_alt' => 'Two Department representatives beside the Niuean invasive-species programme vehicle',
            ],
            [
                'slug' => 'department-of-environment-is-happy-to-work-together-with-villages',
                'title' => 'Department works with villages on invasive-species control',
                'excerpt' => 'Hakupu village and the Department developed a community plan to control invasive weeds, beginning with taro vine.',
                'paragraphs' => [
                    'The Department worked with village communities on practical plans to control invasive species. Hakupu took an early lead, with its Men\'s Council coordinating work under an agreed village plan.',
                    'The first phase targeted taro vine at several village sites. The work formed part of the GEF-6 Regional Invasives Project and reinforced that invasive-species control requires participation across the community.',
                ],
                'category' => 'Environment Updates',
                'published_at' => '2021-02-19 11:00:00',
                'source_date_label' => '19 February 2021',
                'source_url' => 'https://niuewastemanagement.nu/2021/02/19/department-of-environment-is-happy-to-work-together-with-villages/',
                'image' => 'images/news/waste-management-archive/hakupu-village-invasive-weeds.jpg',
                'image_alt' => 'Department and Hakupu community representatives meeting about invasive-species control',
            ],
            [
                'slug' => 'invasive-species-awareness-programme',
                'title' => 'Invasive-species awareness programme reaches students',
                'excerpt' => 'Niue High School students joined a field-based awareness programme focused on recognising and managing invasive species.',
                'paragraphs' => [
                    'The Department began field trips for an invasive-species awareness programme aimed at younger generations. Year 8 students from Niue High School took part after beginning the topic in class.',
                    'Delivered through the GEF-6 Regional Invasives Project, the programme combined classroom knowledge with outdoor learning and planned further sessions for other year groups.',
                ],
                'category' => 'Environment Updates',
                'published_at' => '2021-02-19 10:00:00',
                'source_date_label' => '19 February 2021',
                'source_url' => 'https://niuewastemanagement.nu/2021/02/19/invasive-species-awareness-programme/',
                'image' => 'images/news/waste-management-archive/invasive-species-awareness.jpg',
                'image_alt' => 'Niue High School students taking part in an invasive-species field programme',
            ],
            [
                'slug' => 'fakalofa-lahi-atu-lakepa-maleloa',
                'title' => 'Waste collection pilot supports Lakepa Show Day',
                'excerpt' => 'A Department rubbish truck provided early general-waste collection for Lakepa village and show-day vendors.',
                'paragraphs' => [
                    'During the 2020 Lakepa Show Day, the Department stationed a rubbish truck at the village green as a pilot service for the village and stallholders.',
                    'The early collection was intended to improve waste handling and reduce odour and pests over the weekend. General waste was accepted during the event, while recyclable material remained scheduled for the regular collection service.',
                ],
                'category' => 'Community',
                'published_at' => '2021-02-19 09:00:00',
                'source_date_label' => '19 February 2021',
                'source_url' => 'https://niuewastemanagement.nu/2021/02/19/fakalofa-lahi-atu-lakepa-maleloa/',
                'image' => 'images/news/waste-management-archive/lakepa-show-day-waste-truck.jpg',
                'image_alt' => 'Niue Waste Recycling Centre collection truck used for the Lakepa Show Day pilot',
            ],
        ];
    }
}
