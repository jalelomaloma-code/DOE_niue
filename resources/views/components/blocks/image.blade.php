<figure class="mx-auto max-w-4xl px-4 py-8">
    <img src="{{ $data['url'] ?? '' }}"
         alt="{{ $data['alt'] ?? '' }}"
         class="w-full rounded" loading="lazy">

    @if (! empty($data['caption']))
        <figcaption class="mt-2 text-sm text-text/70">{{ $data['caption'] }}</figcaption>
    @endif
</figure>
