<x-layouts.public :title="$programme->seoTitle()" :description="$programme->seoDescription()">
    <article>
        <header class="bg-surface">
            <div class="mx-auto max-w-3xl px-4 py-12">
                <h1 class="text-3xl font-bold text-brand sm:text-4xl">{{ $programme->title }}</h1>

                @if ($programme->summary)
                    <p class="mt-4 text-lg">{{ $programme->summary }}</p>
                @endif
            </div>
        </header>

        @if ($url = $programme->featuredImageUrl('hero'))
            <img src="{{ $url }}" alt="{{ $programme->featuredImageAlt() ?? '' }}"
                 class="h-64 w-full object-cover sm:h-96">
        @else
            <div class="h-64 w-full bg-brand/10 sm:h-96" aria-hidden="true"></div>
        @endif

        {{-- Sanitised on save by the `body` mutator on the Programme model
             (App\Models\Concerns\HasSanitisedRichText -> RichTextSanitiser),
             NOT by ProgrammeForm -- so seeder, tinker and import writes are
             covered too, not just Filament saves. Never render unsanitised
             input. --}}
        <div class="prose prose-slate mx-auto max-w-3xl px-4 py-8">
            {!! $programme->body !!}
        </div>

        @if ($projects->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 pb-12">
                <h2 class="mb-6 text-2xl font-bold text-brand">Related Projects</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-content.programme-card :programme="$project" />
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</x-layouts.public>
