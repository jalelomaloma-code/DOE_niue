@props(['document'])

<li class="flex flex-col gap-4 border-b border-black/10 py-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="font-semibold text-ink">{{ $document->title }}</p>
        <p class="mt-1 text-sm text-text/70">
            @if ($document->category)
                <span>{{ $document->category->name }}</span>
                <span aria-hidden="true"> &middot; </span>
            @endif
            @if ($document->published_date)
                <time datetime="{{ $document->published_date->toDateString() }}">{{ $document->published_date->format('d M Y') }}</time>
                <span aria-hidden="true"> &middot; </span>
            @endif
            @if ($type = $document->fileType())
                <span>{{ $type }}</span>
            @endif
            @if ($size = $document->fileSizeForHumans())
                <span aria-hidden="true"> &middot; </span>
                <span>{{ $size }}</span>
            @endif
        </p>
    </div>

    @if ($url = $document->downloadUrl())
        <x-ui.button variant="secondary" :href="$url" class="shrink-0">
            Download<span class="sr-only"> {{ $document->title }}</span>
        </x-ui.button>
    @endif
</li>
