@props(['images' => []])

<div {{ $attributes->class(['mt-5 grid gap-3', count($images) >= 4 ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-3']) }}>
    @foreach ($images as $image)
        <figure class="overflow-hidden rounded border border-black/10 bg-white">
            <img src="{{ asset($image['src']) }}"
                 alt="{{ $image['alt'] }}"
                 class="h-28 w-full object-cover"
                 loading="lazy">
        </figure>
    @endforeach
</div>
