@php
    // $navigation is shared by the View::composer in AppServiceProvider
    // (one query, reused by the header's two lists and this footer).
    //
    // Pre-Spec-2 this column pair filtered the old twelve-item flat IA into
    // "Environment Programmes" and "Resources" by hardcoded URL list. That
    // regrouped down to six top-level items whose children now live on each
    // section's own landing page rather than in primary nav, so the same
    // split can no longer be derived from $navigation at all — it collapses
    // into a single database-driven "Site Links" column instead. Not titled
    // "Quick Links": that heading is already used, with different content
    // (the QuickLink-backed homepage cards), further up the same page.
    $legalPages = \App\Models\Page::published()->whereIn('slug', ['privacy', 'terms', 'accessibility'])->orderBy('sort_order')->get();

    // The "Government of Niue" column (social links) is deliberately absent:
    // neither social URL is set in this dev environment. A heading with
    // nothing under it reads as broken, not deferred, so the whole column
    // is dropped rather than rendered empty — it comes back once it has
    // real content. The same rule applies to the legal column below, which
    // is empty until Task 11 seeds the Privacy/Terms/Accessibility pages.
@endphp

<footer class="on-dark bg-ink text-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-3">
        <div>
            <p class="text-lg font-bold">{{ $settings->department_name }}</p>
            <p class="mt-1 text-sm text-white/80">{{ $settings->government_name }}</p>

            <ul class="mt-4 space-y-1 text-sm text-white/80">
                @if ($settings->address)
                    <li class="flex min-h-11 items-center">{{ $settings->address }}</li>
                @endif
                @if ($settings->phone)
                    <li><a href="tel:{{ $settings->phone }}" class="flex min-h-11 items-center hover:text-white hover:underline">{{ $settings->phone }}</a></li>
                @endif
                @if ($settings->email)
                    <li><a href="mailto:{{ $settings->email }}" class="flex min-h-11 items-center hover:text-white hover:underline">{{ $settings->email }}</a></li>
                @endif
                @if ($settings->office_hours)
                    <li class="flex min-h-11 items-center">{{ $settings->office_hours }}</li>
                @endif
            </ul>
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Site Links</p>

            <ul class="mt-4 space-y-1 text-sm text-white/80">
                @foreach ($navigation as $item)
                    <li><a href="{{ $item->url }}" class="flex min-h-11 items-center hover:text-white hover:underline">{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </div>

        @if ($legalPages->isNotEmpty())
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Legal</p>

                <ul class="mt-4 space-y-1 text-sm text-white/80">
                    @foreach ($legalPages as $page)
                        <li><a href="{{ url($page->path) }}" class="flex min-h-11 items-center hover:text-white hover:underline">{{ $page->title }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @if ($settings->footer_text)
        <div class="border-t border-white/10 px-4 py-4 text-center text-sm text-white/60">
            {{ $settings->footer_text }}
        </div>
    @endif
</footer>
