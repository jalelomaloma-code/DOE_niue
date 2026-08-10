<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Public digital services</p>
                    <h2 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">Website Analytics</h2>
                    <p class="mt-2 max-w-3xl text-base text-gray-500 dark:text-gray-400">
                        Sample Director view of public website reach, content demand, service engagement and mobile performance.
                    </p>
                </div>
                <x-filament::button color="primary" icon="heroicon-o-arrow-down-tray" wire:click="exportAnalyticsSnapshot">
                    Export analytics
                </x-filament::button>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($this->engagement() as $metric)
                    <div class="rounded-2xl bg-[#f4f8f7] p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <p class="text-3xl font-black text-[#0b8f8f]">{{ $metric['value'] }}</p>
                        <p class="mt-2 text-base font-bold text-[#153243] dark:text-white">{{ $metric['label'] }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $metric['trend'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-xl font-semibold text-[#153243] dark:text-white">Traffic sources</h3>
                <div class="mt-5 space-y-4">
                    @foreach ($this->analyticsChannels() as $channel)
                        <div>
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-base font-bold text-[#153243] dark:text-white">{{ $channel['label'] }}</p>
                                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $channel['value'] }}</p>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                <div class="h-full rounded-full bg-[#0b8f8f]" style="width: {{ $channel['share'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-xl font-semibold text-[#153243] dark:text-white">Top public content</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <tr>
                                <th class="py-3 pr-4 font-semibold">Content</th>
                                <th class="py-3 pr-4 font-semibold">Views</th>
                                <th class="py-3 font-semibold">Director note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($this->topWebsiteContent() as $content)
                                <tr>
                                    <td class="py-4 pr-4 text-base font-bold text-[#153243] dark:text-white">{{ $content['title'] }}</td>
                                    <td class="py-4 pr-4 text-base font-semibold text-gray-600 dark:text-gray-300">{{ $content['views'] }}</td>
                                    <td class="py-4 text-base text-gray-600 dark:text-gray-300">{{ $content['action'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-xl font-semibold text-[#153243] dark:text-white">Referrers</h3>
                <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Sample source domains sending visitors to the Department website.</p>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <tr>
                                <th class="py-3 pr-4 font-semibold">Source</th>
                                <th class="py-3 pr-4 font-semibold">Type</th>
                                <th class="py-3 pr-4 font-semibold">Visits</th>
                                <th class="py-3 font-semibold">Director note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($this->referrers() as $referrer)
                                <tr>
                                    <td class="py-4 pr-4 text-base font-bold text-[#153243] dark:text-white">{{ $referrer['source'] }}</td>
                                    <td class="py-4 pr-4 text-base text-gray-600 dark:text-gray-300">{{ $referrer['type'] }}</td>
                                    <td class="py-4 pr-4 text-base font-semibold text-gray-600 dark:text-gray-300">{{ $referrer['visits'] }}</td>
                                    <td class="py-4 text-base text-gray-600 dark:text-gray-300">{{ $referrer['note'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-xl font-semibold text-[#153243] dark:text-white">Referrer actions</h3>
                <div class="mt-5 space-y-4">
                    @foreach ($this->referrerActions() as $action)
                        <div class="rounded-xl bg-[#f4f8f7] p-4 dark:bg-white/5">
                            <p class="text-base font-semibold text-[#153243] dark:text-white">{{ $action }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($this->digitalServiceHealth() as $health)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $health['label'] }}</p>
                    <p class="mt-3 text-2xl font-black text-[#153243] dark:text-white">{{ $health['value'] }}</p>
                    <x-filament::badge class="mt-3" color="{{ $health['status'] === 'Watch' ? 'warning' : 'success' }}">
                        {{ $health['status'] }}
                    </x-filament::badge>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
