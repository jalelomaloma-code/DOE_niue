<header>
    <div class="on-dark bg-brand text-white">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div>
                    <p class="text-lg font-bold leading-tight">{{ $settings->department_name }}</p>
                    <p class="text-sm text-white/80">{{ $settings->government_name }}</p>
                </div>
            </a>

            <button type="button"
                    id="menu-toggle"
                    aria-expanded="false"
                    aria-controls="primary-nav"
                    class="ml-auto flex min-h-11 min-w-11 items-center justify-center rounded p-2 lg:hidden">
                <span class="sr-only">Open main menu</span>
                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6 stroke-current" fill="none" stroke-width="2">
                    <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" />
                </svg>
            </button>
        </div>
    </div>

    <nav aria-label="Primary" class="border-b border-black/10 bg-white">
        <ul id="primary-nav" class="mx-auto hidden max-w-7xl flex-wrap px-4 lg:flex">
            @foreach (config('navigation.primary') as $item)
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
    </nav>
</header>
