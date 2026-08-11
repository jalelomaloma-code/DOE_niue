<x-layouts.public :title="$title" :description="$intro">
    <article class="flex flex-1 flex-col">
        <x-ui.page-header
            :title="$title"
            :intro="$intro"
            eyebrow="Department Branch"
            :image="$image"
            image-alt="" />

        <section class="flex-1 bg-white">
            <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:py-12">
                <div class="max-w-3xl">
                    <h2 class="text-2xl font-bold text-ink">Branch focus areas</h2>
                    <p class="mt-2 text-text/80">The branch leads work across the following environmental priorities.</p>
                </div>

                <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($items as $item)
                        <li class="border-l-4 border-eco bg-surface p-5 font-semibold text-ink shadow-sm">
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </article>
</x-layouts.public>
