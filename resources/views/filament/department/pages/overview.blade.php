<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Department performance</p>
            <h2 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">Overview</h2>
            <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Fictional summary of programmes, projects, incidents and reporting pressure.</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($this->metrics() as $metric)
                    <div class="rounded-2xl bg-[#f4f8f7] p-5 dark:bg-white/5">
                        <p class="text-3xl font-black text-[#0b8f8f]">{{ $metric['value'] }}</p>
                        <p class="mt-2 text-base font-bold text-[#153243] dark:text-white">{{ $metric['label'] }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $metric['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-xl font-semibold text-[#153243] dark:text-white">Programme status</h3>
            <div class="mt-5 space-y-5">
                @foreach ($this->programmeStatus() as $programme)
                    <div>
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-base font-bold text-[#153243] dark:text-white">{{ $programme['name'] }}</p>
                            <x-filament::badge color="{{ $programme['status'] === 'Action needed' ? 'warning' : 'success' }}">{{ $programme['status'] }}</x-filament::badge>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div class="h-full rounded-full bg-[#0b8f8f]" style="width: {{ $programme['progress'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
