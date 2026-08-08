@php
    $navItems = config('navigation.primary');
@endphp

<header>
    <div class="on-dark bg-brand text-white">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div>
                    <p class="text-lg font-bold leading-tight">{{ $settings->department_name }}</p>
                    <p class="text-sm text-white/80">{{ $settings->government_name }}</p>
                </div>
            </a>
        </div>
    </div>

    <nav aria-label="Primary" class="border-b border-black/10 bg-white">
        {{-- Desktop: a plain, permanently visible list — no disclosure
             involved, so it's just an ordinary display override. --}}
        <ul class="mx-auto hidden max-w-7xl flex-wrap px-4 lg:flex">
            @foreach ($navItems as $item)
                @php $isCurrent = request()->is(ltrim($item['url'], '/') ?: '/'); @endphp
                <li>
                    <a href="{{ $item['url'] }}"
                       @if ($isCurrent) aria-current="page" @endif
                       @class([
                           'inline-flex min-h-11 items-center border-b-2 px-3 py-2 text-sm font-semibold text-ink hover:border-accent',
                           'border-accent' => $isCurrent,
                           'border-transparent' => ! $isCurrent,
                       ])>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Mobile: a native <details>/<summary> disclosure — the browser
             opens and closes this with zero JavaScript, so the menu still
             works if the script fails to load. This list is a deliberate
             duplicate of the one above, not a shared/re-shown element: in
             Chromium a closed <details>'s body is hidden by internal
             shadow-DOM slot assignment, not by `display`, so no CSS
             override — not `display`, not `content-visibility` — can force
             it to render at a breakpoint while closed. Splitting into two
             independently-controlled lists (each a plain, unconditional
             `display: none` at the "wrong" viewport) sidesteps that entirely
             instead of fighting it. Only one list is ever exposed to
             assistive tech at a time, so nothing is double-announced.

             <summary> carries an implicit "button" role whose
             expanded/collapsed state is computed by the browser straight
             from the parent <details> "open" attribute, so most assistive
             tech announces state correctly with no aria-expanded management
             at all. app.js still mirrors it into an explicit aria-expanded
             (belt-and-suspenders for user agents that don't compute it) and
             adds the one thing native <details> can't do: closing on Escape
             and returning focus to the toggle. --}}
        <details id="nav-disclosure" class="lg:hidden">
            <summary id="menu-toggle"
                     aria-controls="mobile-nav"
                     class="mx-auto flex max-w-7xl min-h-11 cursor-pointer list-none items-center gap-2 px-4 py-3 text-sm font-semibold text-ink select-none marker:hidden [&::-webkit-details-marker]:hidden">
                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6 stroke-current" fill="none" stroke-width="2">
                    <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" />
                </svg>
                Menu
            </summary>

            <ul id="mobile-nav" class="mx-auto max-w-7xl list-none px-4 pb-4">
                @foreach ($navItems as $item)
                    @php $isCurrent = request()->is(ltrim($item['url'], '/') ?: '/'); @endphp
                    <li>
                        <a href="{{ $item['url'] }}"
                           @if ($isCurrent) aria-current="page" @endif
                           @class([
                               'inline-flex min-h-11 items-center border-b-2 px-3 py-2 text-sm font-semibold text-ink hover:border-accent',
                               'border-accent' => $isCurrent,
                               'border-transparent' => ! $isCurrent,
                           ])>
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </details>
    </nav>
</header>
