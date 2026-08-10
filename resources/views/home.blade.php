<x-layouts.public :description="$homepage->hero_intro">

    {{-- Hero. Static image, no carousel: LCP and accessibility. --}}
    <section class="on-dark relative bg-ink text-white" data-hero-slider>
        @php
            $slides = [
                $homepage->getFirstMediaUrl('hero_image', 'hero') ?: asset('images/demo/homepage-hero.png'),
                asset('images/demo/homepage-hero-forest.png'),
                asset('images/demo/homepage-hero-marine.png'),
                asset('images/demo/homepage-hero-waste.png'),
            ];
        @endphp

        <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
            @foreach ($slides as $index => $slide)
                <img src="{{ $slide }}"
                     alt=""
                     data-hero-slide
                     @class([
                         'absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-700',
                         'opacity-45' => $index === 0,
                     ])>
            @endforeach
        </div>

        <div class="relative mx-auto max-w-4xl px-4 py-20 text-center sm:py-28">
            <h1 class="text-3xl font-bold leading-tight sm:text-5xl">
                {{-- ENT_NOQUOTES, not {{ }}: this text node has no attribute
                     boundary to protect, so quote characters render literally
                     instead of as &#039; entities. Still escapes <, >, & --}}
                {!! htmlspecialchars($homepage->hero_headline ?? '', ENT_NOQUOTES, 'UTF-8') !!}
            </h1>

            @if ($homepage->hero_intro)
                <p class="mx-auto mt-6 max-w-2xl text-lg text-white/90">{{ $homepage->hero_intro }}</p>
            @endif

            <div class="mt-8 flex flex-wrap justify-center gap-4">
                @if ($homepage->hero_primary_cta_label)
                    <x-ui.button variant="accent" :href="$homepage->hero_primary_cta_url">
                        {{ $homepage->hero_primary_cta_label }}
                    </x-ui.button>
                @endif

                @if ($homepage->hero_secondary_cta_label)
                    <x-ui.button variant="secondary" :href="$homepage->hero_secondary_cta_url">
                        {{ $homepage->hero_secondary_cta_label }}
                    </x-ui.button>
                @endif
            </div>

            <div class="mt-10 flex items-center justify-center gap-3" aria-label="Hero slides">
                <button type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/40 bg-black/20 text-white hover:bg-black/35"
                        data-hero-prev
                        aria-label="Previous hero image">
                    <span aria-hidden="true">&larr;</span>
                </button>

                <div class="flex gap-2">
                    @foreach ($slides as $index => $slide)
                        <button type="button"
                                @class([
                                    'h-3 w-3 rounded-full border border-white/70',
                                    'bg-white' => $index === 0,
                                    'bg-white/20' => $index !== 0,
                                ])
                                data-hero-dot="{{ $index }}"
                                aria-label="Show hero image {{ $index + 1 }}"
                                @if ($index === 0) aria-current="true" @endif></button>
                    @endforeach
                </div>

                <button type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/40 bg-black/20 text-white hover:bg-black/35"
                        data-hero-next
                        aria-label="Next hero image">
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </div>
    </section>

    @if ($quickLinks->isNotEmpty())
        <x-ui.section :heading="$homepage->quick_links_heading">
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($quickLinks as $link)
                    <li>
                        @php
                            $imageClass = match ($link->label) {
                                'Environment Programmes' => 'from-[#0f6b55] via-[#287A4B] to-[#8ccf95]',
                                'Waste & Recycling' => 'from-[#37505c] via-[#287A4B] to-[#d4e15f]',
                                'Biodiversity' => 'from-[#204b38] via-[#2f7c51] to-[#f2c94c]',
                                'Climate & Marine' => 'from-[#003A70] via-[#0477a8] to-[#62c6c4]',
                                'Publications' => 'from-[#5a6580] via-[#003A70] to-[#c7d5e8]',
                                'Report an Issue' => 'from-[#7a2e2e] via-[#b4442d] to-[#FCD116]',
                                default => 'from-brand via-eco to-accent',
                            };
                        @endphp
                        <a href="{{ $link->url }}"
                           class="group flex h-full flex-col items-center rounded border border-black/10 bg-white p-6 text-center shadow-sm hover:border-brand">
                            <span class="flex h-20 w-20 items-center justify-center rounded-full bg-linear-to-br {{ $imageClass }} text-white ring-1 ring-black/10" aria-hidden="true">
                                <x-ui.quick-link-icon :icon="$link->icon" class="h-11 w-11" />
                            </span>
                            <span class="mt-4 block">
                                <span class="block text-lg font-semibold text-brand group-hover:underline">{{ $link->label }}</span>
                                @if ($link->description)
                                    <span class="mt-2 block text-sm">{{ $link->description }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($programmes->isNotEmpty())
        <x-ui.section :heading="$homepage->programmes_heading" :intro="$homepage->programmes_intro">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($programmes as $programme)
                    <x-content.programme-card :programme="$programme" />
                @endforeach
            </div>
        </x-ui.section>
    @endif

    @if ($news->isNotEmpty())
        <x-ui.section :heading="$homepage->news_heading">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($news as $article)
                    <x-content.news-card :article="$article" />
                @endforeach
            </div>
        </x-ui.section>
    @endif

    @if ($projects->isNotEmpty())
        <x-ui.section :heading="$homepage->projects_heading" class="bg-surface">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    <x-content.programme-card :programme="$project" />
                @endforeach
            </div>
        </x-ui.section>
    @endif

    @if ($documents->isNotEmpty())
        <x-ui.section :heading="$homepage->resources_heading">
            <ul class="divide-y divide-black/10">
                @foreach ($documents as $document)
                    <x-content.document-row :document="$document" />
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    @if ($homepage->report_heading)
        <section class="bg-brand py-12 text-white sm:py-16">
            <div class="mx-auto max-w-3xl px-4 text-center">
                <h2 class="text-2xl font-bold sm:text-3xl">{{ $homepage->report_heading }}</h2>

                @if ($homepage->report_intro)
                    <p class="mt-2 text-white/90">{{ $homepage->report_intro }}</p>
                @endif

                @if ($homepage->report_cta_label)
                    <div class="mt-8">
                        <x-ui.button variant="accent" :href="$homepage->report_cta_url">
                            {{ $homepage->report_cta_label }}
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </section>
    @endif
</x-layouts.public>
