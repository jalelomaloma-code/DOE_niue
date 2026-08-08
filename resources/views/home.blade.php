<x-layouts.public :description="$homepage->hero_intro">

    {{-- Hero. Static image, no carousel: LCP and accessibility. --}}
    <section class="on-dark relative bg-ink text-white">
        @if ($hero = $homepage->getFirstMediaUrl('hero_image', 'hero'))
            <img src="{{ $hero }}" alt="" aria-hidden="true"
                 class="absolute inset-0 h-full w-full object-cover opacity-40">
        @endif

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
        </div>
    </section>

    @if ($quickLinks->isNotEmpty())
        <x-ui.section :heading="$homepage->quick_links_heading">
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($quickLinks as $link)
                    <li>
                        <a href="{{ $link->url }}"
                           class="block h-full rounded border border-black/10 bg-white p-6 hover:border-brand">
                            <span class="block font-semibold text-brand">{{ $link->label }}</span>
                            @if ($link->description)
                                <span class="mt-1 block text-sm">{{ $link->description }}</span>
                            @endif
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
