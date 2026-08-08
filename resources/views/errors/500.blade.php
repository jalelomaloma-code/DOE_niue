<x-layouts.public title="Something went wrong">
    <div class="mx-auto max-w-2xl px-4 py-24 text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-brand">500</p>
        <h1 class="mt-2 text-3xl font-bold text-ink sm:text-4xl">Something went wrong</h1>
        <p class="mt-4 text-text">
            An unexpected error occurred on our end. Please try again shortly.
        </p>

        <div class="mt-8">
            <x-ui.button href="{{ url('/') }}">Return home</x-ui.button>
        </div>
    </div>
</x-layouts.public>
