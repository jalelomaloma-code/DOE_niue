@php
    $programmes = \App\Models\Programme::published()
        ->with('media')
        ->orderBy('sort_order')
        ->take(min((int) ($data['limit'] ?? 12), 100))
        ->get();
@endphp

<section class="mx-auto max-w-7xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    @if ($programmes->isNotEmpty())
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($programmes as $programme)
                <x-content.programme-card :programme="$programme" />
            @endforeach
        </div>
    @endif
</section>
