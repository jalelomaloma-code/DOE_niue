@props(['blocks' => []])

@php
    use App\Enums\BlockType;
@endphp

@foreach (($blocks ?? []) as $block)
    @php
        $type = BlockType::tryFrom($block['type'] ?? '');

        // A block type removed from the code while pages still hold it in
        // their JSON must not take the page down. Losing one section is
        // recoverable; a 500 on a live government page is not.
        if (! $type) {
            \Illuminate\Support\Facades\Log::warning('Unknown page block type', [
                'type' => $block['type'] ?? null,
            ]);
        }
    @endphp

    @if ($type)
        @include($type->view(), ['data' => $block['data'] ?? []])
    @endif
@endforeach
