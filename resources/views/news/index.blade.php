<x-layouts.public title="News & Events" description="Latest news and updates from the Niue Department of Environment.">
    <x-ui.page-header
        title="News & Events"
        intro="Updates, announcements and community notices from the Department."
        eyebrow="Department updates" />

    <section class="flex-1 bg-surface">
        <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:py-12">
            @if ($articles->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $article)
                        <x-content.news-card :article="$article" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $articles->links() }}
                </div>
            @else
                <div class="border-l-4 border-eco bg-white p-6 shadow-sm">
                    <p>No news has been published yet.</p>
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
