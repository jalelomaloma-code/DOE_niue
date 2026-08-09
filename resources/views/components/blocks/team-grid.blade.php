@php($members = \App\Models\TeamMember::active()->with('media')->get())

<section class="mx-auto max-w-7xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    @if ($members->isNotEmpty())
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($members as $member)
                <li class="rounded border border-black/10 bg-white p-6 text-center">
                    @if ($url = $member->photoUrl())
                        <img src="{{ $url }}" alt="{{ $member->photoAlt() ?? $member->name }}"
                             class="mx-auto mb-4 h-32 w-32 rounded-full object-cover" loading="lazy">
                    @else
                        <div class="mx-auto mb-4 h-32 w-32 rounded-full bg-brand/10" aria-hidden="true"></div>
                    @endif

                    <h3 class="font-semibold text-brand">{{ $member->name }}</h3>
                    {{-- displayRole(), not role: appends "(Demo)" for seeded
                         placeholder people, so a reviewer scanning the page can
                         see they are not real staff. See TeamMember. --}}
                    <p class="text-sm text-text/80">{{ $member->displayRole() }}</p>

                    @if ($member->bio)
                        <p class="mt-2 text-sm">{{ $member->bio }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
