<x-layouts.public title="Report an Environmental Issue" description="How to report pollution, illegal dumping or environmental damage to the Niue Department of Environment.">
    <x-ui.page-header
        title="Report an Environmental Issue"
        intro="For the demo, this page shows the intended reporting pathway without collecting live submissions."
        eyebrow="Public reporting" />

    <section class="flex-1 bg-surface">
        <div class="mx-auto grid w-full max-w-7xl gap-6 px-4 py-10 sm:py-12 lg:grid-cols-[1fr_0.85fr] lg:items-start">
        <div class="rounded-lg border border-black/10 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-2xl font-bold text-ink">What to report</h2>
            <ul class="mt-4 list-disc space-y-2 pl-6">
                <li>Pollution, spills or unusual discharge.</li>
                <li>Illegal dumping or unsafe waste disposal.</li>
                <li>Damage to protected areas, reefs or coastal habitats.</li>
                <li>Other urgent environmental concerns.</li>
            </ul>
        </div>

        <div class="rounded-lg border border-black/10 bg-white p-6 shadow-sm sm:p-8">
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
        </div>
    </section>
</x-layouts.public>
