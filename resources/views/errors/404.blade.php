<x-layouts.public title="Page not found">
    <section class="public-status-page flex flex-1 items-center bg-[#eef6f1]">
        <div class="mx-auto w-full max-w-2xl px-4 py-16 text-center">
            <p class="text-sm font-semibold uppercase text-eco">404</p>
            <h1 class="mt-2 text-3xl font-bold text-ink sm:text-4xl">Page not found</h1>
            <p class="mt-4 text-text">
                The page you are looking for doesn't exist or may have moved.
            </p>

            <div class="mt-8">
                <x-ui.button href="{{ url('/') }}">Return home</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.public>
