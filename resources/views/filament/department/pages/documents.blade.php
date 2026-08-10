<x-filament-panels::page>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm font-bold uppercase tracking-[0.22em] text-[#0b8f8f]">Publications control</p>
        <h2 class="mt-2 text-3xl font-semibold text-[#153243] dark:text-white">Documents</h2>
        <p class="mt-2 text-base text-gray-500 dark:text-gray-400">Fictional publication and report queue for Director visibility.</p>
        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                    <tr>
                        <th class="py-3 pr-4 font-semibold">Document</th>
                        <th class="py-3 pr-4 font-semibold">Type</th>
                        <th class="py-3 pr-4 font-semibold">Owner</th>
                        <th class="py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($this->documents() as $document)
                        <tr>
                            <td class="py-4 pr-4 text-base font-bold text-[#153243] dark:text-white">{{ $document['title'] }}</td>
                            <td class="py-4 pr-4 text-base text-gray-600 dark:text-gray-300">{{ $document['type'] }}</td>
                            <td class="py-4 pr-4 text-base text-gray-600 dark:text-gray-300">{{ $document['owner'] }}</td>
                            <td class="py-4"><x-filament::badge color="{{ $document['status'] === 'Ready' ? 'success' : 'warning' }}">{{ $document['status'] }}</x-filament::badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
