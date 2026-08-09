@php($heading = $data['heading'] ?? null)

<section class="mx-auto max-w-3xl px-4 py-8">
    @if ($heading)
        <h2 class="mb-4 text-2xl font-bold text-brand">{{ $heading }}</h2>
    @endif

    {{-- Sanitised on save by RichTextSanitiser; never render unsanitised input. --}}
    <div class="prose prose-slate max-w-none">
        {!! $data['body'] ?? '' !!}
    </div>
</section>
