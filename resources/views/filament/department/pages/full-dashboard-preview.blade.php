<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl bg-primary-600 p-6 text-white shadow-sm ring-1 ring-primary-500/20">
            <x-filament::badge color="warning" size="lg">Optional Full Department Dashboard Preview</x-filament::badge>
            <h2 class="mt-4 text-2xl font-bold tracking-tight sm:text-3xl">Director executive summary</h2>
            <p class="mt-2 max-w-4xl text-base text-primary-50">
                Full operational dashboard concept using fictional sample data only. This preview does not process HR actions or store real employee information.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->executiveMetrics() as $metric)
                <x-filament::section>
                    <p class="text-base font-medium text-gray-600 dark:text-gray-300">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $metric['value'] }}</p>
                    <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $metric['note'] }}</p>
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-filament::section>
                <x-slot name="heading">Pending approvals</x-slot>
                <div class="space-y-4">
                    @foreach ($this->approvals() as $approval)
                        <div>
                            <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $approval['title'] }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $approval['owner'] }} · {{ $approval['status'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Staff and leave overview</x-slot>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Aggregate fictional counts only.</p>
                <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    @foreach ($this->staffOverview() as $item)
                        <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <p class="text-2xl font-bold text-primary-700 dark:text-primary-300">{{ $item['value'] }}</p>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $item['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section id="monthly-report">
                <x-slot name="heading">Monthly Director report preview</x-slot>
                <p class="text-base text-gray-700 dark:text-gray-200">
                    Demo pack: executive summary, budget snapshot, risks, incidents, compliance and engagement.
                </p>
                <x-filament::button class="mt-5" color="warning" icon="heroicon-o-document-arrow-down" wire:click="previewMonthlyReport">
                    Preview report
                </x-filament::button>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Project budgets and expenditure</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <tr>
                            <th class="py-3 pr-4 font-semibold">Programme</th>
                            <th class="py-3 pr-4 font-semibold">Budget</th>
                            <th class="py-3 pr-4 font-semibold">Spent</th>
                            <th class="py-3 font-semibold">Variance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->budgets() as $budget)
                            <tr>
                                <td class="py-4 pr-4 text-base font-semibold text-gray-950 dark:text-white">{{ $budget['programme'] }}</td>
                                <td class="py-4 pr-4 text-base text-gray-700 dark:text-gray-200">{{ $budget['budget'] }}</td>
                                <td class="py-4 pr-4 text-base text-gray-700 dark:text-gray-200">{{ $budget['spent'] }}</td>
                                <td class="py-4 text-base text-gray-700 dark:text-gray-200">{{ $budget['variance'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Programme milestones and risks</x-slot>
                <div class="space-y-4">
                    @foreach ($this->milestones() as $milestone)
                        <div class="border-l-4 border-warning-400 pl-4">
                            <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $milestone['title'] }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Risk: {{ $milestone['risk'] }} · Next: {{ $milestone['next'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Compliance deadlines</x-slot>
                <div class="space-y-4">
                    @foreach ($this->compliance() as $item)
                        <div>
                            <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $item['title'] }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $item['date'] }} · {{ $item['status'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Environmental incident register</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <tr>
                                <th class="py-3 pr-4 font-semibold">ID</th>
                                <th class="py-3 pr-4 font-semibold">Type</th>
                                <th class="py-3 pr-4 font-semibold">Priority</th>
                                <th class="py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($this->incidents() as $incident)
                                <tr>
                                    <td class="py-4 pr-4 text-base font-semibold text-gray-950 dark:text-white">{{ $incident['id'] }}</td>
                                    <td class="py-4 pr-4 text-base text-gray-700 dark:text-gray-200">{{ $incident['type'] }}</td>
                                    <td class="py-4 pr-4">
                                        <x-filament::badge color="{{ $incident['priority'] === 'High' ? 'danger' : 'primary' }}">{{ $incident['priority'] }}</x-filament::badge>
                                    </td>
                                    <td class="py-4 text-base text-gray-700 dark:text-gray-200">{{ $incident['status'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Community engagement statistics</x-slot>
                <div class="space-y-4">
                    @foreach ($this->community() as $item)
                        <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $item['activity'] }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $item['location'] }} · {{ $item['reach'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
