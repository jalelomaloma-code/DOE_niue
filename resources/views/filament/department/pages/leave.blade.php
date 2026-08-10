<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Roster assurance</p>
            <h2 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">Leave and roster decisions</h2>
            <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Fictional leave queue with sample staff names only.</p>
        </div>
        <div class="grid gap-4 xl:grid-cols-3">
            @foreach ($this->requests() as $request)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-lg font-black text-[#153243] dark:text-white">{{ $request['name'] }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $request['id'] }} - {{ $request['type'] }}</p>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $request['team'] }} - {{ $request['dates'] }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $request['impact'] }} impact</p>
                    @if (isset($this->leaveDecisionStatuses[$request['id']]))
                        <x-filament::badge class="mt-4" color="success">{{ $this->leaveDecisionStatuses[$request['id']] }}</x-filament::badge>
                    @else
                        <div class="mt-5 flex gap-2">
                            <x-filament::button color="gray" wire:click="decide('{{ $request['id'] }}', 'Held for roster')">Hold</x-filament::button>
                            <x-filament::button color="success" wire:click="decide('{{ $request['id'] }}', 'Approved')">Approve</x-filament::button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-2xl font-semibold text-[#153243] dark:text-white">Leave records</h3>
                    <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Fictional leave register for Director visibility, including Annual Leave, Sick Leave and TOIL.</p>
                </div>
                <x-filament::badge color="primary" size="lg">Sample records</x-filament::badge>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <tr>
                            <th class="py-3 pr-4 font-semibold">Staff member</th>
                            <th class="py-3 pr-4 font-semibold">Leave type</th>
                            <th class="py-3 pr-4 font-semibold">Period</th>
                            <th class="py-3 pr-4 font-semibold">Days</th>
                            <th class="py-3 pr-4 font-semibold">Annual available</th>
                            <th class="py-3 pr-4 font-semibold">Sick available</th>
                            <th class="py-3 pr-4 font-semibold">TOIL available</th>
                            <th class="py-3 pr-4 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->records() as $record)
                            <tr>
                                <td class="py-4 pr-4 text-base font-bold text-[#153243] dark:text-white">{{ $record['name'] }}</td>
                                <td class="py-4 pr-4 text-base text-gray-600 dark:text-gray-300">{{ $record['type'] }}</td>
                                <td class="py-4 pr-4 text-base text-gray-600 dark:text-gray-300">{{ $record['period'] }}</td>
                                <td class="py-4 pr-4 text-base font-semibold text-gray-600 dark:text-gray-300">{{ $record['days'] }}</td>
                                <td class="py-4 pr-4 text-base text-gray-600 dark:text-gray-300">{{ $record['annual'] }}</td>
                                <td class="py-4 pr-4 text-base text-gray-600 dark:text-gray-300">{{ $record['sick'] }}</td>
                                <td class="py-4 pr-4 text-base text-gray-600 dark:text-gray-300">{{ $record['toil'] }}</td>
                                <td class="py-4 pr-4">
                                    <x-filament::badge color="{{ $record['status'] === 'Approved' ? 'success' : ($record['status'] === 'Pending' ? 'warning' : 'primary') }}">
                                        {{ $record['status'] }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-2xl font-semibold text-[#153243] dark:text-white">Leave balances available</h3>
            <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Sample balance view for Annual Leave, Sick Leave and TOIL.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($this->balances() as $balance)
                    <div class="rounded-2xl bg-[#f4f8f7] p-5 dark:bg-white/5">
                        <p class="text-base font-bold text-[#153243] dark:text-white">{{ $balance['name'] }}</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-gray-500 dark:text-gray-400">Annual</dt>
                                <dd class="font-bold text-[#0b8f8f]">{{ $balance['annual'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-gray-500 dark:text-gray-400">Sick</dt>
                                <dd class="font-bold text-[#0b8f8f]">{{ $balance['sick'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-gray-500 dark:text-gray-400">TOIL</dt>
                                <dd class="font-bold text-[#0b8f8f]">{{ $balance['toil'] }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
