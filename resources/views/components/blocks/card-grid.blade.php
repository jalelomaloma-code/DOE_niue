<section class="mx-auto max-w-7xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (($data['cards'] ?? []) as $card)
            <li class="rounded border border-black/10 bg-white p-6">
                <h3 class="font-semibold text-brand">
                    @if (! empty($card['url']))
                        <a href="{{ $card['url'] }}" class="hover:underline">{{ $card['title'] ?? '' }}</a>
                    @else
                        {{ $card['title'] ?? '' }}
                    @endif
                </h3>

                @if (! empty($card['text']))
                    <p class="mt-2 text-sm">{{ $card['text'] }}</p>
                @endif
            </li>
        @endforeach
    </ul>
</section>
