@props(['heading' => null, 'intro' => null])

<section {{ $attributes->class(['py-12 sm:py-16']) }}>
    <div class="mx-auto max-w-7xl px-4">
        @if ($heading)
            <h2 class="text-2xl font-bold text-ink sm:text-3xl">{{ $heading }}</h2>
        @endif

        @if ($intro)
            <p class="mt-2 max-w-2xl text-text">{{ $intro }}</p>
        @endif

        <div @class(['mt-8' => $heading || $intro])>
            {{ $slot }}
        </div>
    </div>
</section>
