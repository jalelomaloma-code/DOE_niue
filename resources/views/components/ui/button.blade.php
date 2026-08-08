@props(['variant' => 'primary', 'href' => null])

@php
$classes = match ($variant) {
    // Yellow is never a button background with white text; the accent
    // variant always carries ink text.
    'primary'   => 'bg-brand text-white hover:bg-brand/90',
    'secondary' => 'bg-white text-brand border-2 border-brand hover:bg-brand/5',
    'accent'    => 'bg-accent text-ink hover:bg-accent/90',
};
@endphp

<{{ $href ? 'a' : 'button' }}
    @if ($href) href="{{ $href }}" @else type="button" @endif
    {{ $attributes->class(['inline-flex min-h-11 items-center justify-center rounded px-6 py-3 font-semibold', $classes]) }}>
    {{ $slot }}
</{{ $href ? 'a' : 'button' }}>
