@php
    // Accent is a background carrying ink text — never text on white.
    $tone = ($data['tone'] ?? 'info') === 'warning'
        ? 'bg-accent text-ink'
        : 'bg-brand/5 text-text border-l-4 border-brand';
@endphp

<aside class="mx-auto max-w-3xl px-4 py-8">
    <div class="rounded p-6 {{ $tone }}">
        @if (! empty($data['heading']))
            <h2 class="mb-2 text-lg font-bold">{{ $data['heading'] }}</h2>
        @endif

        <p>{{ $data['body'] ?? '' }}</p>
    </div>
</aside>
