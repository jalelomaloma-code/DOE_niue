@props([
    'title',
    'intro' => null,
    'eyebrow' => null,
    'image' => null,
    'imageAlt' => '',
])

<header {{ $attributes->class(['public-page-header']) }}>
    <div @class([
        'relative mx-auto grid w-full max-w-7xl gap-7 px-4 py-8 sm:py-10',
        'lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.72fr)] lg:items-center' => $image,
    ])>
        <div class="relative z-10 max-w-4xl">
            @if ($eyebrow)
                <p class="text-sm font-semibold uppercase text-eco">{{ $eyebrow }}</p>
            @endif

            <h1 @class([
                'text-3xl font-bold leading-tight text-brand sm:text-4xl',
                'mt-2' => $eyebrow,
            ])>{{ $title }}</h1>

            @if ($intro)
                <p class="mt-3 max-w-3xl text-lg leading-relaxed text-text">{{ $intro }}</p>
            @endif

            @if (isset($meta))
                <div class="mt-4 text-sm text-text/75">
                    {{ $meta }}
                </div>
            @endif

            @if (trim((string) $slot) !== '')
                <div class="mt-4">
                    {{ $slot }}
                </div>
            @endif
        </div>

        @if ($image)
            <div class="relative z-10 overflow-hidden rounded-lg border border-white/70 bg-white shadow-sm">
                <img src="{{ $image }}"
                     alt="{{ $imageAlt }}"
                     class="h-56 w-full object-cover sm:h-64 lg:h-72"
                     loading="eager"
                     fetchpriority="high">
            </div>
        @else
            <img src="{{ asset('images/niue-doe-logo.png') }}"
                 alt=""
                 aria-hidden="true"
                 class="public-page-header__mark hidden md:block">
        @endif
    </div>
</header>
