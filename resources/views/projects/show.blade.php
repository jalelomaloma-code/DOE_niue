<x-layouts.public :title="$project->seoTitle()" :description="$project->seoDescription()">
    <article>
        <header class="bg-surface">
            <div class="mx-auto max-w-3xl px-4 py-12">
                @if ($project->programme)
                    <p class="text-sm font-semibold uppercase text-brand">{{ $project->programme->title }}</p>
                @endif

                <h1 class="mt-2 text-3xl font-bold text-brand sm:text-4xl">{{ $project->title }}</h1>

                @if ($project->summary)
                    <p class="mt-4 text-lg">{{ $project->summary }}</p>
                @endif

                <p class="mt-4 inline-flex rounded bg-white px-3 py-1 text-sm font-semibold text-ink">
                    {{ $project->project_status->label() }}
                </p>
            </div>
        </header>

        @php $imageUrl = $project->featuredImageUrl('hero') ?: \App\Support\DemoImages::for($project); @endphp
        <img src="{{ $imageUrl }}" alt="{{ $project->featuredImageAlt() ?? '' }}" class="h-64 w-full object-cover sm:h-96">

        <div class="prose prose-slate mx-auto max-w-3xl px-4 py-8">
            {!! $project->body !!}
        </div>
    </article>
</x-layouts.public>
