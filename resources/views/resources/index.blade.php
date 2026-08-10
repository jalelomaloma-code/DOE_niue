<x-layouts.public title="Resources" description="Reports, policies, legislation, forms and public documents from the Niue Department of Environment.">
    <header class="bg-surface">
        <div class="mx-auto max-w-3xl px-4 py-12">
            <h1 class="text-3xl font-bold text-brand sm:text-4xl">Resources</h1>
            <p class="mt-4 text-lg">Reports, policies, legislation, forms and other public documents.</p>
        </div>
    </header>

    <section class="mx-auto max-w-7xl px-4 py-12">
        @if ($categories->isNotEmpty())
            <div class="mb-8 flex flex-wrap gap-2">
                @foreach ($categories as $category)
                    <span class="rounded border border-black/10 bg-white px-3 py-2 text-sm font-semibold text-ink">
                        {{ $category->name }}
                    </span>
                @endforeach
            </div>
        @endif

        @if ($documents->isNotEmpty())
            <ul class="divide-y divide-black/10 rounded border border-black/10 bg-white px-4">
                @foreach ($documents as $document)
                    <x-content.document-row :document="$document" />
                @endforeach
            </ul>

            <div class="mt-8">
                {{ $documents->links() }}
            </div>
        @else
            <p>No resources have been published yet.</p>
        @endif
    </section>
</x-layouts.public>
