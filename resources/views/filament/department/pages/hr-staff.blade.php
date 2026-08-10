<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Director workforce view</p>
                    <h2 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">HR & Staff</h2>
                    <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Fictional staff and branch roster information for demonstration only.</p>
                </div>
                <x-filament::button color="primary" icon="heroicon-o-arrow-down-tray" wire:click="exportWorkforceSnapshot">Export snapshot</x-filament::button>
            </div>
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

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($this->workers() as $worker)
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0b8f8f] text-sm font-black text-white">
                        {{ collect(explode(' ', $worker['name']))->map(fn ($part) => $part[0])->take(2)->implode('') }}
                    </div>
                    <p class="mt-4 text-base font-bold text-[#153243] dark:text-white">{{ $worker['name'] }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $worker['role'] }}</p>
                    <p class="mt-3 text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $worker['unit'] }}</p>
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $worker['today'] }}</span>
                        <x-filament::badge color="{{ $worker['status'] === 'Leave' ? 'warning' : ($worker['status'] === 'Field' ? 'primary' : 'success') }}">{{ $worker['status'] }}</x-filament::badge>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
