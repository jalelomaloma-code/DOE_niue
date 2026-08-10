<x-layouts.public :title="$title" :description="$intro">
    <article>
        <header class="bg-surface">
            <div class="mx-auto max-w-3xl px-4 py-12">
                <p class="text-sm font-semibold uppercase text-brand">Department Branch</p>
                <h1 class="mt-2 text-3xl font-bold text-brand sm:text-4xl">{{ $title }}</h1>
                <p class="mt-4 text-lg">{{ $intro }}</p>
            </div>
        </header>

        <img src="{{ $image }}" alt="" class="h-64 w-full object-cover sm:h-96" loading="lazy">

        <section class="mx-auto max-w-3xl px-4 py-12">
            <h2 class="text-2xl font-bold text-ink">Branch focus areas</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($items as $item)
                    <li class="rounded border border-black/10 bg-white p-5 font-semibold text-ink">
                        {{ $item }}
                    </li>
                @endforeach
            </ul>
        </section>
    </article>
</x-layouts.public>
