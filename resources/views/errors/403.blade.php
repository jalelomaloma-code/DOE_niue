<x-layouts.public title="Access denied">
    <section class="public-status-page flex flex-1 items-center bg-[#eef6f1]">
        <div class="mx-auto w-full max-w-2xl px-4 py-16 text-center">
            <p class="text-sm font-semibold uppercase text-eco">403</p>
            <h1 class="mt-2 text-3xl font-bold text-ink sm:text-4xl">Access denied</h1>
            <p class="mt-4 text-text">
                You don't have permission to view this page.
            </p>

            <div class="mt-8">
                <x-ui.button href="{{ url('/') }}">Return home</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.public>
