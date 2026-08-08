@props(['href' => null])

<{{ $href ? 'a' : 'div' }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['block rounded-lg border border-black/10 bg-white p-6 shadow-sm', $href ? 'hover:shadow-md hover:border-brand/30 transition-shadow' : '']) }}>
    {{ $slot }}
</{{ $href ? 'a' : 'div' }}>
