@props(['icon' => null])

@php
    $path = match ($icon) {
        'heroicon-o-trash' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-9 0V5h6v2m-8 0 1 12h8l1-12M10 11v5m4-5v5" />',
        'heroicon-o-sparkles' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.7 4.3L18 9l-4.3 1.7L12 15l-1.7-4.3L6 9l4.3-1.7L12 3Zm6 10 .9 2.1L21 16l-2.1.9L18 19l-.9-2.1L15 16l2.1-.9L18 13ZM5 13l1 2.5L8.5 17 6 18.5 5 21l-1-2.5L1.5 17 4 15.5 5 13Z" />',
        'heroicon-o-cloud' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 18h10a4 4 0 0 0 .5-8 6 6 0 0 0-11.3 1.9A3.5 3.5 0 0 0 7 18Z" />',
        'heroicon-o-document-text' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5m-5-8h2" />',
        'heroicon-o-exclamation-triangle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4 3 20h18L12 4Zm0 6v4m0 3h.01" />',
        default => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 0c2.4 2.5 3.6 5.5 3.6 9S14.4 18.5 12 21m0-18c-2.4 2.5-3.6 5.5-3.6 9S9.6 18.5 12 21M4 12h16" />',
    };
@endphp

<svg {{ $attributes->class('h-9 w-9') }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
    {!! $path !!}
</svg>
