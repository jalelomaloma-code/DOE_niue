<x-layouts.public :title="$article->seoTitle()" :description="$article->seoDescription()">
    <article>
        <header class="bg-surface">
            <div class="mx-auto max-w-3xl px-4 py-12">
                <p class="text-sm font-semibold uppercase text-brand">
                    {{ $article->category?->name ?? 'News' }}
                </p>
                <h1 class="mt-2 text-3xl font-bold text-brand sm:text-4xl">{{ $article->title }}</h1>

                @if ($article->published_at)
                    <p class="mt-4 text-sm text-text/70">
                        <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d M Y') }}</time>
                        <span aria-hidden="true"> &middot; </span>
                        {{ $article->byline() }}
                    </p>
                @endif
            </div>
        </header>

        @php $imageUrl = $article->featuredImageUrl('hero') ?: \App\Support\DemoImages::for($article); @endphp
        <img src="{{ $imageUrl }}" alt="{{ $article->featuredImageAlt() ?? '' }}" class="h-64 w-full object-cover sm:h-96">

        <div class="prose prose-slate mx-auto max-w-3xl px-4 py-8">
            {!! $article->body !!}
        </div>
    </article>
</x-layouts.public>
