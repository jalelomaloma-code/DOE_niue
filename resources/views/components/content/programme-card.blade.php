@props(['programme'])

{{--
    Reused for both programmes (homepage "Our Environment Programmes" section)
    and projects (homepage "Featured Projects" section) — both models expose
    the same title/summary/featured-image surface via HasFeaturedImage, and
    neither has a detail route yet.

    TODO(spec-2/spec-3): link to the programme or project show route once it
    exists — programme detail pages are Spec 2, project detail pages are
    Spec 3. Do not invent a placeholder route in the meantime.
--}}
<a href="#" class="flex h-full flex-col overflow-hidden rounded-lg border border-black/10 bg-white shadow-sm transition-shadow hover:border-brand/30 hover:shadow-md">
    @if ($url = $programme->featuredImageUrl())
        <img src="{{ $url }}" alt="{{ $programme->featuredImageAlt() ?? '' }}" class="h-40 w-full object-cover">
    @else
        <div class="h-40 w-full bg-brand/10" aria-hidden="true"></div>
    @endif

    <div class="flex flex-1 flex-col p-6">
        <h3 class="text-lg font-bold text-ink">{{ $programme->title }}</h3>

        @if ($programme->summary)
            <p class="mt-2 flex-1 text-sm text-text">{{ $programme->summary }}</p>
        @endif
    </div>
</a>
