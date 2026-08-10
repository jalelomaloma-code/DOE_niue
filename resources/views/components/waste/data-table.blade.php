@props(['headers' => []])

<div class="overflow-x-auto rounded border border-black/10">
    <table class="w-full min-w-[34rem] border-collapse text-left text-sm">
        <thead class="bg-brand text-white">
            <tr>
                @foreach ($headers as $header)
                    <th scope="col" class="px-4 py-3 font-semibold">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-black/10 bg-white text-text">
            {{ $slot }}
        </tbody>
    </table>
</div>
