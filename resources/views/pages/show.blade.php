<x-layouts.public :title="$page->seoTitle()" :description="$page->seoDescription()">
    <article>
        <header class="bg-surface">
            <div class="mx-auto max-w-3xl px-4 py-12">
                <h1 class="text-3xl font-bold text-brand sm:text-4xl">{{ $page->title }}</h1>

                @if ($page->intro)
                    <p class="mt-4 text-lg">{{ $page->intro }}</p>
                @endif
            </div>
        </header>

        <x-page.content :blocks="$page->content" />

        @if ($children->isNotEmpty())
            <nav aria-label="In this section" class="mx-auto max-w-3xl px-4 py-8">
                <h2 class="mb-4 text-2xl font-bold text-brand">In this section</h2>
                <ul class="space-y-2">
                    @foreach ($children as $child)
                        <li>
                            <a href="/{{ $child->path }}"
                               class="inline-flex min-h-11 items-center text-brand hover:underline">
                                {{ $child->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($siblings->count() > 1)
            <nav aria-label="Related pages" class="mx-auto max-w-3xl px-4 pb-12">
                <ul class="flex flex-wrap gap-4">
                    @foreach ($siblings as $sibling)
                        <li>
                            <a href="/{{ $sibling->path }}"
                               @if ($sibling->is($page)) aria-current="page" @endif
                               @class([
                                   'inline-flex min-h-11 items-center border-b-2 px-1 text-sm font-semibold',
                                   'border-accent text-ink' => $sibling->is($page),
                                   'border-transparent text-brand hover:border-brand' => ! $sibling->is($page),
                               ])>
                                {{ $sibling->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </article>
</x-layouts.public>
