@php
    $navigation = collect(config('navigation.primary'));

    $programmeUrls = ['/environment-programmes', '/waste-and-recycling', '/biodiversity-and-conservation', '/climate-and-marine', '/projects'];
    $resourceUrls = ['/news', '/resources', '/gallery', '/vacancies', '/contact'];

    $programmeLinks = $navigation->whereIn('url', $programmeUrls);
    $resourceLinks = $navigation->whereIn('url', $resourceUrls);
@endphp

<footer class="on-dark bg-ink text-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="text-lg font-bold">{{ $settings->department_name }}</p>
            <p class="mt-1 text-sm text-white/80">{{ $settings->government_name }}</p>

            <ul class="mt-4 space-y-2 text-sm text-white/80">
                @if ($settings->address)
                    <li>{{ $settings->address }}</li>
                @endif
                @if ($settings->phone)
                    <li><a href="tel:{{ $settings->phone }}" class="hover:text-white hover:underline">{{ $settings->phone }}</a></li>
                @endif
                @if ($settings->email)
                    <li><a href="mailto:{{ $settings->email }}" class="hover:text-white hover:underline">{{ $settings->email }}</a></li>
                @endif
                @if ($settings->office_hours)
                    <li>{{ $settings->office_hours }}</li>
                @endif
            </ul>
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Government of Niue</p>

            <ul class="mt-4 space-y-2 text-sm text-white/80">
                @if ($settings->facebook_url)
                    <li><a href="{{ $settings->facebook_url }}" class="hover:text-white hover:underline">Facebook</a></li>
                @endif
                @if ($settings->youtube_url)
                    <li><a href="{{ $settings->youtube_url }}" class="hover:text-white hover:underline">YouTube</a></li>
                @endif
            </ul>
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Environment Programmes</p>

            <ul class="mt-4 space-y-2 text-sm text-white/80">
                @foreach ($programmeLinks as $item)
                    <li><a href="{{ $item['url'] }}" class="hover:text-white hover:underline">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Resources</p>

            <ul class="mt-4 space-y-2 text-sm text-white/80">
                @foreach ($resourceLinks as $item)
                    <li><a href="{{ $item['url'] }}" class="hover:text-white hover:underline">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($settings->footer_text)
        <div class="border-t border-white/10 px-4 py-4 text-center text-sm text-white/60">
            {{ $settings->footer_text }}
        </div>
    @endif
</footer>
