<x-layouts.public title="Projects" description="Current and planned environmental projects delivered by the Niue Department of Environment.">
    <x-ui.page-header
        title="Projects"
        intro="Individual projects delivered under the Department's environmental programmes."
        eyebrow="Our work" />

    <section class="flex-1 bg-surface">
        <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:py-12">
            @if ($projects->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-content.programme-card :programme="$project" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $projects->links() }}
                </div>
            @else
                <div class="border-l-4 border-eco bg-white p-6 shadow-sm">
                    <p>No projects have been published yet.</p>
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
