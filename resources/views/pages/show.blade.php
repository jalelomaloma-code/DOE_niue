<x-layouts.public :title="$page->seoTitle()" :description="$page->seoDescription()">
    <article>
        @if ($page->path === 'about')
            <header class="bg-ink text-white">
                <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                    <div>
                        <p class="text-sm font-semibold uppercase text-accent">Niue Department of Environment</p>
                        <h1 class="mt-3 text-4xl font-bold leading-tight sm:text-5xl">{{ $page->title }}</h1>

                        @if ($page->intro)
                            <p class="mt-5 max-w-2xl text-lg text-white/85">{{ $page->intro }}</p>
                        @endif
                    </div>

                    <img src="{{ asset('images/demo/environmental-governance.png') }}"
                         alt=""
                         class="h-72 w-full rounded object-cover shadow-lg"
                         loading="eager">
                </div>
            </header>

            <section class="bg-white">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 lg:grid-cols-[0.9fr_1.1fr]">
                    <div>
                        <h2 class="text-2xl font-bold text-brand">Our Role</h2>
                        <p class="mt-4 text-text">
                            The Department leads environmental protection, conservation, waste management and climate resilience work for Niue.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded border border-black/10 bg-surface p-5">
                            <p class="text-sm font-semibold uppercase text-brand">Policy</p>
                            <p class="mt-2 font-semibold text-ink">Environmental governance and responsible decision-making.</p>
                        </div>
                        <div class="rounded border border-black/10 bg-surface p-5">
                            <p class="text-sm font-semibold uppercase text-brand">Protection</p>
                            <p class="mt-2 font-semibold text-ink">Biodiversity, conservation and habitat stewardship.</p>
                        </div>
                        <div class="rounded border border-black/10 bg-surface p-5">
                            <p class="text-sm font-semibold uppercase text-brand">Resilience</p>
                            <p class="mt-2 font-semibold text-ink">Climate change, ozone and coastal adaptation priorities.</p>
                        </div>
                        <div class="rounded border border-black/10 bg-surface p-5">
                            <p class="text-sm font-semibold uppercase text-brand">Services</p>
                            <p class="mt-2 font-semibold text-ink">Waste management and pollution control guidance.</p>
                        </div>
                    </div>
                </div>
            </section>
        @else
            <header class="bg-surface">
                <div class="mx-auto max-w-3xl px-4 py-12">
                    <h1 class="text-3xl font-bold text-brand sm:text-4xl">{{ $page->title }}</h1>

                    @if ($page->intro)
                        <p class="mt-4 text-lg">{{ $page->intro }}</p>
                    @endif
                </div>
            </header>
        @endif

        <x-page.content :blocks="$page->content" />

        @if ($children->isNotEmpty())
            <nav aria-label="In this section" class="mx-auto max-w-7xl px-4 py-10">
                <h2 class="mb-6 text-2xl font-bold text-brand">In this section</h2>
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($children as $child)
                        <li>
                            <a href="/{{ $child->path }}"
                               class="block h-full rounded border border-black/10 bg-white p-5 shadow-sm hover:border-brand">
                                <span class="block font-semibold text-brand">{{ $child->title }}</span>
                                @if ($child->intro)
                                    <span class="mt-2 block text-sm text-text">{{ $child->intro }}</span>
                                @endif
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
