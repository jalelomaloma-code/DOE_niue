@php
    use Illuminate\Support\Facades\Log;

    // We never invent alt text — a guessed value or filename misinforms
    // assistive tech as much as silence does — so a missing alt renders as
    // alt="", which is still better than a screen reader announcing a raw
    // filename. But alt="" also tells assistive tech this image is purely
    // decorative, which is untrue for a content image. Filament requires
    // alt at upload (Task 6), but seeders and any future non-Filament write
    // path bypass that entirely, so warn here — the same way the dispatcher
    // warns on an unknown block type — to catch it when it happens.
    if (empty($data['alt'])) {
        Log::warning('Image block rendered without alt text', ['url' => $data['url'] ?? null]);
    }
@endphp

<figure class="mx-auto max-w-4xl px-4 py-8">
    <img src="{{ $data['url'] ?? '' }}"
         alt="{{ $data['alt'] ?? '' }}"
         class="w-full rounded" loading="lazy">

    @if (! empty($data['caption']))
        <figcaption class="mt-2 text-sm text-text/70">{{ $data['caption'] }}</figcaption>
    @endif
</figure>
