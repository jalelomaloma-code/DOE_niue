<x-layouts.public title="Projects" description="Current and planned environmental projects delivered by the Niue Department of Environment.">
    <header class="bg-surface">
        <div class="mx-auto max-w-3xl px-4 py-12">
            <h1 class="text-3xl font-bold text-brand sm:text-4xl">Projects</h1>
            <p class="mt-4 text-lg">Individual projects delivered under the Department's environmental programmes.</p>
        </div>
    </header>

    <section class="mx-auto max-w-7xl px-4 py-12">
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
            <p>No projects have been published yet.</p>
        @endif
    </section>
</x-layouts.public>
