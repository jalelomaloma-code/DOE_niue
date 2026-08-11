<x-layouts.public :title="$project->seoTitle()" :description="$project->seoDescription()">
    <article class="flex flex-1 flex-col">
        @php $imageUrl = $project->featuredImageUrl('hero') ?: \App\Support\DemoImages::for($project); @endphp
        <x-ui.page-header
            :title="$project->title"
            :intro="$project->summary"
            :eyebrow="$project->programme?->title ?? 'Department Project'"
            :image="$imageUrl"
            :image-alt="$project->featuredImageAlt() ?? ''">
            <span class="inline-flex rounded border border-brand/15 bg-white px-3 py-1 text-sm font-semibold text-ink shadow-sm">
                {{ $project->project_status->label() }}
            </span>
        </x-ui.page-header>

        <div class="flex-1 bg-white">
            <div class="public-rich-text mx-auto max-w-3xl px-4 py-10 sm:py-12">
                {!! $project->body !!}
            </div>
        </div>
    </article>
</x-layouts.public>
