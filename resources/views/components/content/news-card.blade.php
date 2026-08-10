@props(['article'])

<article class="flex h-full flex-col overflow-hidden rounded-lg border border-black/10 bg-white shadow-sm">
    @php $imageUrl = \App\Support\DemoImages::cardFor($article); @endphp
    <img src="{{ $imageUrl }}" alt="{{ $article->featuredImageAlt() ?? '' }}" class="h-40 w-full object-cover">

    <div class="flex flex-1 flex-col p-6">
        <p class="text-sm text-text/70">
            @if ($article->published_at)
                <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d M Y') }}</time>
            @endif
            @if ($article->category)
                <span aria-hidden="true"> &middot; </span>
                <span>{{ $article->category->name }}</span>
            @endif
        </p>

        <h3 class="mt-2 text-lg font-bold text-ink">{{ $article->title }}</h3>

        @if ($article->excerpt)
            <p class="mt-2 flex-1 text-sm text-text">{{ $article->excerpt }}</p>
        @endif

        <a href="{{ route('news.show', $article->slug) }}" class="mt-4 inline-block text-sm font-semibold text-brand hover:underline">
            Read more<span class="sr-only"> about {{ $article->title }}</span>
        </a>
    </div>
</article>
