<x-layouts.public :title="$programme->seoTitle()" :description="$programme->seoDescription()">
    <article class="flex flex-1 flex-col">
        @php $imageUrl = $programme->featuredImageUrl('hero') ?: \App\Support\DemoImages::for($programme); @endphp
        <x-ui.page-header
            :title="$programme->title"
            :intro="$programme->summary"
            eyebrow="Environment Programme"
            :image="$imageUrl"
            :image-alt="$programme->featuredImageAlt() ?? ''" />

        {{-- Sanitised on save by the `body` mutator on the Programme model
             (App\Models\Concerns\HasSanitisedRichText -> RichTextSanitiser),
             NOT by ProgrammeForm -- so seeder, tinker and import writes are
             covered too, not just Filament saves. Never render unsanitised
             input. --}}
        <div class="bg-white">
            <div class="public-rich-text mx-auto max-w-3xl px-4 py-10 sm:py-12">
                {!! $programme->body !!}
            </div>
        </div>

        @if ($projects->isNotEmpty())
            <section class="bg-surface">
                <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:py-12">
                    <h2 class="mb-6 text-2xl font-bold text-brand">Related Projects</h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($projects as $project)
                            <x-content.programme-card :programme="$project" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </article>
</x-layouts.public>
