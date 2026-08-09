@props(['blocks' => []])

@php
    use App\Enums\BlockType;
    use Illuminate\Support\Facades\Log;
@endphp

@foreach (($blocks ?? []) as $block)
    @php
        // BlockType::tryFrom() requires a string. PHP's weak typing coerces
        // int/float/bool to string, but not array or object — those throw an
        // uncaught TypeError instead of returning null, which would fatal
        // the page instead of skipping the block. Guard the type explicitly.
        $rawType = $block['type'] ?? null;
        $type = is_string($rawType) ? BlockType::tryFrom($rawType) : null;

        // A block type removed from the code while pages still hold it in
        // their JSON must not take the page down. Losing one section is
        // recoverable; a 500 on a live government page is not.
        if (! $type) {
            Log::warning('Unknown page block type', [
                'type' => $rawType,
            ]);
        }
    @endphp

    @if ($type)
        @include($type->view(), ['data' => $block['data'] ?? []])
    @endif
@endforeach
