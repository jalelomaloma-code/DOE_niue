<x-layouts.public title="News & Events" description="Latest news and updates from the Niue Department of Environment.">
    <header class="bg-surface">
        <div class="mx-auto max-w-3xl px-4 py-12">
            <h1 class="text-3xl font-bold text-brand sm:text-4xl">News & Events</h1>
            <p class="mt-4 text-lg">Updates, announcements and community notices from the Department.</p>
        </div>
    </header>

    <section class="mx-auto max-w-7xl px-4 py-12">
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
            <p>No news has been published yet.</p>
        @endif
    </section>
</x-layouts.public>
