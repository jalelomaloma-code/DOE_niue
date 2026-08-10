<x-filament-panels::page>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Portal configuration</p>
        <h2 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">Settings</h2>
        <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Non-functional demonstration settings for the Department dashboard concept.</p>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach ($this->settings() as $setting)
                <div class="rounded-2xl bg-[#f4f8f7] p-5 dark:bg-white/5">
                    <p class="text-base font-bold text-[#153243] dark:text-white">{{ $setting['label'] }}</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $setting['value'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
