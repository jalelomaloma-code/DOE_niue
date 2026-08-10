@props(['title' => null, 'intro' => null])

<section {{ $attributes->class(['overflow-hidden rounded border border-black/10 bg-white shadow-sm']) }}>
    @if ($title || $intro)
        <div class="border-b border-black/10 bg-surface px-6 py-5">
            @if ($title)
                <h3 class="text-lg font-bold text-ink">{{ $title }}</h3>
            @endif

            @if ($intro)
                <p class="mt-2 text-sm text-text">{{ $intro }}</p>
            @endif
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>
</section>
