<x-filament-panels::page>
    <div class="space-y-6 bg-[#f4f8f7] text-[#153243] dark:bg-gray-950 dark:text-white">
        <div class="flex flex-col gap-4 border-b border-gray-200 bg-white px-1 pb-5 dark:border-white/10 dark:bg-gray-900 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-base font-semibold text-gray-500 dark:text-gray-300">Niue Department of Environment</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <label class="relative">
                    <span class="sr-only">Search portal</span>
                    <input
                        type="search"
                        placeholder="Search portal"
                        class="h-11 w-56 rounded-xl border-gray-200 bg-gray-50 pl-10 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                    >
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-3 top-3 h-5 w-5 text-gray-400" />
                </label>
                <div class="hidden h-8 border-l border-gray-200 dark:border-white/10 lg:block"></div>
                <div class="flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-600 dark:bg-white/5 dark:text-gray-200">
                    <span>View as</span>
                    <span class="text-[#153243] dark:text-white">Director</span>
                    <x-filament::icon icon="heroicon-o-chevron-down" class="h-4 w-4" />
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0b8f8f] text-base font-bold text-white">HT</div>
                    <div>
                        <p class="text-base font-bold text-[#153243] dark:text-white">Haden Talagi</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Director</p>
                    </div>
                </div>
            </div>
        </div>

        <section class="space-y-6 px-1 py-2">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-[#0b8f8f]">Executive command view · {{ strtoupper($this->period) }}</p>
                    <h2 class="mt-4 text-4xl font-semibold tracking-tight text-[#153243] dark:text-white">Director overview</h2>
                    <p class="mt-3 text-lg text-gray-600 dark:text-gray-300">
                        Decisions, delivery, finances, compliance and environmental response in one place.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-filament::button color="gray" icon="heroicon-o-plus" size="lg" wire:click="recordIncident">
                        Record incident
                    </x-filament::button>
                    <x-filament::button color="primary" icon="heroicon-o-arrow-down-tray" size="lg" wire:click="downloadMonthlyReport">
                        Monthly report
                    </x-filament::button>
                </div>
            </div>

            <div id="overview" class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($this->summaryCards() as $card)
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center gap-5">
                            <div @class([
                                'flex h-14 w-14 items-center justify-center rounded-2xl',
                                'bg-amber-100 text-amber-600' => $loop->first,
                                'bg-blue-100 text-blue-600' => $loop->iteration === 2,
                                'bg-teal-100 text-teal-700' => $loop->iteration === 3,
                                'bg-green-100 text-green-700' => $loop->iteration === 4,
                            ])>
                                <x-filament::icon :icon="$card['icon']" class="h-7 w-7" />
                            </div>
                            <div>
                                <p class="text-base font-bold text-gray-500 dark:text-gray-300">{{ $card['label'] }}</p>
                                <p class="mt-2 text-3xl font-black text-[#153243] dark:text-white">{{ $card['value'] }}</p>
                                <p @class([
                                    'mt-2 text-sm font-semibold',
                                    'text-green-700 dark:text-green-400' => $loop->last,
                                    'text-gray-500 dark:text-gray-400' => ! $loop->last,
                                ])>{{ $card['note'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-2xl font-semibold text-[#153243] dark:text-white">1. Director approvals</h3>
                        <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Purchases, travel and documents requiring a decision</p>
                    </div>
                            <x-filament::badge color="info" size="lg">{{ $this->pendingApprovalCount() }} pending</x-filament::badge>
                </div>
                <div class="mt-6 space-y-4">
                    @foreach ($this->approvals() as $approval)
                        <div class="flex flex-col gap-4 rounded-xl bg-white p-4 ring-1 ring-gray-100 dark:bg-white/5 dark:ring-white/10 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-base font-black text-green-700">{{ $approval['code'] }}</div>
                                <div>
                                    <p class="text-lg font-bold text-[#153243] dark:text-white">{{ $approval['title'] }}</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $approval['meta'] }}</p>
                                </div>
                            </div>
                            @if (isset($this->approvalStatuses[$approval['title']]))
                                <x-filament::badge color="{{ $this->approvalStatuses[$approval['title']] === 'Approved' ? 'success' : 'warning' }}" size="lg">
                                    {{ $this->approvalStatuses[$approval['title']] }}
                                </x-filament::badge>
                            @else
                                <div class="flex gap-2">
                                    <x-filament::button color="gray" wire:click="declineApproval('{{ addslashes($approval['title']) }}')">Decline</x-filament::button>
                                    <x-filament::button color="success" wire:click="approveApproval('{{ addslashes($approval['title']) }}')">Approve</x-filament::button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <div id="staff" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Director workforce view</p>
                            <h3 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">HR & Staff command summary</h3>
                            <p class="mt-2 max-w-3xl text-base text-gray-500 dark:text-gray-400">
                                Fictional workforce visibility for branch capacity, field assignments, training readiness and roster risks.
                            </p>
                        </div>
                        <x-filament::button color="primary" icon="heroicon-o-arrow-down-tray" wire:click="exportWorkforceSnapshot">
                            Export snapshot
                        </x-filament::button>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($this->staffSummary() as $item)
                            <div class="rounded-2xl bg-[#f4f8f7] p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                <p class="text-3xl font-black text-[#0b8f8f]">{{ $item['value'] }}</p>
                                <p class="mt-2 text-base font-bold text-[#153243] dark:text-white">{{ $item['label'] }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $item['note'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        @foreach ($this->workforceSignals() as $signal)
                            <div class="rounded-2xl border border-gray-100 p-5 dark:border-white/10">
                                <p class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $signal['label'] }}</p>
                                <p class="mt-3 text-2xl font-black text-[#153243] dark:text-white">{{ $signal['value'] }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $signal['trend'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                                <tr>
                                    <th class="py-3 pr-4 font-semibold">Branch unit</th>
                                    <th class="py-3 pr-4 font-semibold">Lead</th>
                                    <th class="py-3 pr-4 font-semibold">Available</th>
                                    <th class="py-3 pr-4 font-semibold">Capacity</th>
                                    <th class="py-3 pr-4 font-semibold">Risk</th>
                                    <th class="py-3 font-semibold">Director focus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                @foreach ($this->staffUnits() as $unit)
                                    <tr>
                                        <td class="py-4 pr-4 text-base font-bold text-[#153243] dark:text-white">{{ $unit['unit'] }}</td>
                                        <td class="py-4 pr-4 text-base font-semibold text-gray-600 dark:text-gray-300">{{ $unit['lead'] }}</td>
                                        <td class="py-4 pr-4 text-base font-semibold text-gray-600 dark:text-gray-300">{{ $unit['available'] }}</td>
                                        <td class="py-4 pr-4">
                                            <x-filament::badge color="{{ $unit['capacity'] === 'Busy' || $unit['capacity'] === 'Field work heavy' ? 'warning' : 'success' }}">
                                                {{ $unit['capacity'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="py-4 pr-4">
                                            <x-filament::badge color="{{ $unit['risk'] === 'Medium' ? 'warning' : 'success' }}">
                                                {{ $unit['risk'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="py-4 text-base text-gray-600 dark:text-gray-300">{{ $unit['focus'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 rounded-2xl bg-[#f7fbfb] p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <h4 class="text-xl font-semibold text-[#153243] dark:text-white">Staff directory snapshot</h4>
                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                            @foreach ($this->workers() as $worker)
                                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0b8f8f] text-sm font-black text-white">
                                            {{ collect(explode(' ', $worker['name']))->map(fn ($part) => $part[0])->take(2)->implode('') }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-base font-bold text-[#153243] dark:text-white">{{ $worker['name'] }}</p>
                                            <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $worker['role'] }}</p>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $worker['unit'] }}</p>
                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $worker['today'] }}</span>
                                        <x-filament::badge color="{{ $worker['status'] === 'Leave' ? 'warning' : ($worker['status'] === 'Field' ? 'primary' : 'success') }}">
                                            {{ $worker['status'] }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl bg-[#f7fbfb] p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <h4 class="text-xl font-semibold text-[#153243] dark:text-white">Workforce priorities for Director attention</h4>
                        <div class="mt-4 grid gap-3 lg:grid-cols-3">
                            @foreach ($this->staffPriorities() as $priority)
                                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-base font-bold text-[#153243] dark:text-white">{{ $priority['title'] }}</p>
                                        <x-filament::badge color="{{ $priority['priority'] === 'Medium' ? 'warning' : 'primary' }}">
                                            {{ $priority['priority'] }}
                                        </x-filament::badge>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $priority['owner'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $priority['status'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div id="leave" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Roster assurance</p>
                            <h3 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">Leave and roster decisions</h3>
                            <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Aggregate sample leave view, with no real employee records.</p>
                        </div>
                        <x-filament::badge color="warning" size="lg">{{ $this->pendingLeaveCount() }} pending</x-filament::badge>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        @foreach ($this->leaveSummary() as $item)
                            <div class="rounded-2xl bg-[#f4f8f7] p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                <p class="text-2xl font-black text-[#0b8f8f]">{{ $item['value'] }}</p>
                                <p class="mt-1 text-base font-bold text-[#153243] dark:text-white">{{ $item['label'] }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $item['note'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <h4 class="text-xl font-semibold text-[#153243] dark:text-white">Leave decision queue</h4>
                        <div class="mt-4 space-y-4">
                            @foreach ($this->leaveRequests() as $request)
                                <div class="rounded-2xl border border-gray-100 p-4 dark:border-white/10">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-base font-black text-[#153243] dark:text-white">{{ $request['name'] }}</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $request['id'] }} - {{ $request['type'] }}</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $request['team'] }} - {{ $request['dates'] }}</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $request['roster'] }} - {{ $request['impact'] }} impact</p>
                                        </div>
                                        @if (isset($this->leaveDecisionStatuses[$request['id']]))
                                            <x-filament::badge color="{{ $this->leaveDecisionStatuses[$request['id']] === 'Approved' ? 'success' : 'warning' }}">
                                                {{ $this->leaveDecisionStatuses[$request['id']] }}
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                    @if (! isset($this->leaveDecisionStatuses[$request['id']]))
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <x-filament::button color="gray" size="sm" wire:click="holdLeave('{{ $request['id'] }}')">
                                                Hold for roster
                                            </x-filament::button>
                                            <x-filament::button color="success" size="sm" wire:click="approveLeave('{{ $request['id'] }}')">
                                                Approve leave
                                            </x-filament::button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <h4 class="text-xl font-semibold text-[#153243] dark:text-white">Upcoming leave and roster notes</h4>
                        @foreach ($this->leaveCalendar() as $leave)
                            <div class="rounded-xl border border-gray-100 p-4 dark:border-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-base font-bold text-[#153243] dark:text-white">{{ $leave['name'] }} · {{ $leave['period'] }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $leave['team'] }} - {{ $leave['roster'] }}</p>
                                        <p class="mt-1 text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $leave['impact'] }} impact</p>
                                    </div>
                                    <x-filament::badge color="{{ $leave['status'] === 'Approved' ? 'success' : 'warning' }}">
                                        {{ $leave['status'] }}
                                    </x-filament::badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="analytics" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Public digital services</p>
                        <h3 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">Website Analytics</h3>
                        <p class="mt-2 max-w-3xl text-base text-gray-500 dark:text-gray-400">
                            Sample view of public website reach, content demand, service engagement and mobile performance.
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

                <div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                    <div class="rounded-2xl border border-gray-100 p-5 dark:border-white/10">
                        <h4 class="text-xl font-semibold text-[#153243] dark:text-white">Traffic sources</h4>
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

                    <div class="rounded-2xl border border-gray-100 p-5 dark:border-white/10">
                        <h4 class="text-xl font-semibold text-[#153243] dark:text-white">Top public content</h4>
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

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($this->digitalServiceHealth() as $health)
                        <div class="rounded-2xl bg-white p-5 ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $health['label'] }}</p>
                            <p class="mt-3 text-2xl font-black text-[#153243] dark:text-white">{{ $health['value'] }}</p>
                            <x-filament::badge class="mt-3" color="{{ $health['status'] === 'Watch' ? 'warning' : 'success' }}">
                                {{ $health['status'] }}
                            </x-filament::badge>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-2xl font-semibold text-[#153243] dark:text-white">Environmental indicators</h3>
                    <div class="mt-6 space-y-5">
                        @foreach ($this->indicators() as $indicator)
                            <div>
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-base font-bold text-[#153243] dark:text-white">{{ $indicator['label'] }}</p>
                                    <p class="text-base font-bold text-[#0b8f8f]">{{ $indicator['value'] }}</p>
                                </div>
                                <div class="mt-3 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-[#0b8f8f]" style="width: {{ $indicator['bar'] }}%"></div>
                                </div>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $indicator['status'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="documents" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-2xl font-semibold text-[#153243] dark:text-white">Documents and publications</h3>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach ($this->engagement() as $metric)
                            <div class="rounded-xl bg-[#f4f8f7] p-4 dark:bg-white/5">
                                <p class="text-2xl font-black text-[#0b8f8f]">{{ $metric['value'] }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $metric['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 space-y-3">
                        @foreach ($this->documents() as $document)
                            <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3 dark:border-white/10">
                                <div>
                                    <p class="text-base font-bold text-[#153243] dark:text-white">{{ $document['title'] }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $document['type'] }}</p>
                                </div>
                                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $document['date'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-2xl font-semibold text-[#153243] dark:text-white">Project status</h3>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        @foreach ($this->projectStatus() as $status)
                            <div class="flex items-center justify-between rounded-xl bg-[#f4f8f7] p-4 dark:bg-white/5">
                                <div class="flex items-center gap-3">
                                    <span class="h-3 w-3 rounded-full {{ $status['class'] }}"></span>
                                    <p class="text-base font-bold text-[#153243] dark:text-white">{{ $status['label'] }}</p>
                                </div>
                                <p class="text-xl font-black text-[#153243] dark:text-white">{{ $status['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-2xl font-semibold text-[#153243] dark:text-white">Recent public submissions</h3>
                    <div class="mt-5 space-y-4">
                        @foreach ($this->submissions() as $submission)
                            <div>
                                <p class="text-base font-bold text-[#153243] dark:text-white">{{ $submission['title'] }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $submission['location'] }} · {{ $submission['status'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-2xl font-semibold text-[#153243] dark:text-white">Reporting deadlines</h3>
                    <div class="mt-5 space-y-4">
                        @foreach ($this->deadlines() as $deadline)
                            <div class="border-l-4 border-[#e59a2f] pl-4">
                                <p class="text-base font-bold text-[#153243] dark:text-white">{{ $deadline['title'] }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $deadline['date'] }} · {{ $deadline['owner'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
