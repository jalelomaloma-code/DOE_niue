@php($settings = \App\Models\SiteSetting::current())

<section class="mx-auto max-w-3xl px-4 py-8">
    @if (! empty($data['heading']))
        <h2 class="mb-6 text-2xl font-bold text-brand">{{ $data['heading'] }}</h2>
    @endif

    <dl class="space-y-4">
        @if ($settings->address)
            <div><dt class="font-semibold">Address</dt><dd>{{ $settings->address }}</dd></div>
        @endif
        @if ($settings->phone)
            <div><dt class="font-semibold">Phone</dt>
                <dd><a class="inline-flex min-h-11 items-center text-brand hover:underline" href="tel:{{ $settings->phone }}">{{ $settings->phone }}</a></dd></div>
        @endif
        @if ($settings->email)
            <div><dt class="font-semibold">Email</dt>
                <dd><a class="inline-flex min-h-11 items-center text-brand hover:underline" href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></dd></div>
        @endif
        @if ($settings->office_hours)
            <div><dt class="font-semibold">Office hours</dt><dd>{{ $settings->office_hours }}</dd></div>
        @endif
    </dl>
</section>
