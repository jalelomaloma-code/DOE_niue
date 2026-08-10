<x-layouts.public title="Report an Environmental Issue" description="How to report pollution, illegal dumping or environmental damage to the Niue Department of Environment.">
    <header class="bg-surface">
        <div class="mx-auto max-w-3xl px-4 py-12">
            <h1 class="text-3xl font-bold text-brand sm:text-4xl">Report an Environmental Issue</h1>
            <p class="mt-4 text-lg">For the demo, this page shows the intended reporting pathway without collecting live submissions.</p>
        </div>
    </header>

    <section class="mx-auto max-w-3xl px-4 py-12">
        <div class="rounded border border-black/10 bg-white p-6">
            <h2 class="text-2xl font-bold text-ink">What to report</h2>
            <ul class="mt-4 list-disc space-y-2 pl-6">
                <li>Pollution, spills or unusual discharge.</li>
                <li>Illegal dumping or unsafe waste disposal.</li>
                <li>Damage to protected areas, reefs or coastal habitats.</li>
                <li>Other urgent environmental concerns.</li>
            </ul>
        </div>

        <div class="mt-8 rounded border border-black/10 bg-white p-6">
            <h2 class="text-2xl font-bold text-ink">Contact the Department</h2>
            <p class="mt-4">Please contact the Department directly with the location, date, description and any photos you can safely provide.</p>

            <dl class="mt-6 space-y-4">
                @if ($settings->email)
                    <div>
                        <dt class="font-semibold text-ink">Email</dt>
                        <dd><a href="mailto:{{ $settings->email }}" class="text-brand hover:underline">{{ $settings->email }}</a></dd>
                    </div>
                @endif

                @if ($settings->phone)
                    <div>
                        <dt class="font-semibold text-ink">Phone</dt>
                        <dd><a href="tel:{{ $settings->phone }}" class="text-brand hover:underline">{{ $settings->phone }}</a></dd>
                    </div>
                @endif

                @if ($settings->address)
                    <div>
                        <dt class="font-semibold text-ink">Office</dt>
                        <dd>{{ $settings->address }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </section>
</x-layouts.public>
