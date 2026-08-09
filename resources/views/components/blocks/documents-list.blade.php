@php
    $documents = \App\Models\Document::published()
        ->with('category', 'media')
        ->when(! empty($data['category_id']), fn ($q) => $q->where('document_category_id', $data['category_id']))
        ->latest('published_date')
        ->take($data['limit'] ?? 10)
        ->get();
@endphp

<section class="mx-auto max-w-7xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    @if ($documents->isNotEmpty())
        <ul class="divide-y divide-black/10">
            @foreach ($documents as $document)
                <x-content.document-row :document="$document" />
            @endforeach
        </ul>
    @endif
</section>
