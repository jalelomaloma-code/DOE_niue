<x-layouts.public title="Resources" description="Reports, policies, legislation, forms and public documents from the Niue Department of Environment.">
    <x-ui.page-header
        title="Resources"
        intro="Reports, policies, legislation, forms and other public documents."
        eyebrow="Public information" />

    <section class="flex-1 bg-surface">
        <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:py-12">
            @if ($categories->isNotEmpty())
                <div class="mb-6 flex flex-wrap gap-2" aria-label="Resource categories">
                    @foreach ($categories as $category)
                        <span class="rounded border border-black/10 bg-white px-3 py-2 text-sm font-semibold text-ink">
                            {{ $category->name }}
                        </span>
                    @endforeach
                </div>
            @endif

            @if ($documents->isNotEmpty())
                <ul class="divide-y divide-black/10 rounded-lg border border-black/10 bg-white px-4 shadow-sm sm:px-6">
                    @foreach ($documents as $document)
                        <x-content.document-row :document="$document" />
                    @endforeach
                </ul>

                <div class="mt-8">
                    {{ $documents->links() }}
                </div>
            @else
                <div class="border-l-4 border-eco bg-white p-6 shadow-sm">
                    <p>No resources have been published yet.</p>
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
