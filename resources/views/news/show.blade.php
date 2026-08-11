<x-layouts.public :title="$article->seoTitle()" :description="$article->seoDescription()">
    <article class="flex flex-1 flex-col">
        @php $imageUrl = $article->featuredImageUrl('hero') ?: \App\Support\DemoImages::for($article); @endphp
        <x-ui.page-header
            :title="$article->title"
            :eyebrow="$article->category?->name ?? 'News'"
            :image="$imageUrl"
            :image-alt="$article->featuredImageAlt() ?? ''">
            @if ($article->published_at)
                <x-slot:meta>
                    <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d M Y') }}</time>
                    <span aria-hidden="true"> &middot; </span>
                    {{ $article->byline() }}
                </x-slot:meta>
            @endif
        </x-ui.page-header>

        <div class="flex-1 bg-white">
            <div class="public-rich-text mx-auto max-w-3xl px-4 py-10 sm:py-12">
                {!! $article->body !!}
            </div>
        </div>

        @php($galleryImages = $article->getMedia('article_images'))
        @if ($galleryImages->isNotEmpty())
            <section class="bg-surface" aria-labelledby="article-gallery-heading">
                <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:py-12">
                    <h2 id="article-gallery-heading" class="text-2xl font-bold text-brand">Photo gallery</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        @foreach ($galleryImages as $image)
                            <figure class="overflow-hidden rounded-lg border border-black/10 bg-white shadow-sm">
                                <img src="{{ $image->getUrl('hero') }}"
                                     alt="{{ $image->getCustomProperty('alt', '') }}"
                                     class="aspect-[3/2] w-full object-cover"
                                     loading="lazy">
                                <figcaption class="px-4 py-3 text-sm text-text/70">
                                    Photo: Waste Management Niue
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </article>
</x-layouts.public>
