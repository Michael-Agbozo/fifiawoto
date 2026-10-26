<x-layouts::site title="Apply for Support">
    <x-site.page-hero
        title="Ask for help. We're listening."
        breadcrumb="Apply for Support"
        image="images/foundation/community-2.jpg"
        alt="Foundation team meeting with community members"
    >
        The foundation supports widows, children's education, medical needs, people living with disabilities, and community projects across Ghana, Togo, Benin and the United States. If you or someone in your care needs support, start here.
    </x-site.page-hero>

    <section class="py-20 lg:py-24" aria-labelledby="how-support-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="reveal mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">How it works</p>
                <h2 id="how-support-heading" class="mt-3 font-serif text-3xl text-heading sm:text-4xl">Three simple steps.</h2>
            </div>

            <ol class="mt-12 grid gap-5 sm:grid-cols-3">
                @foreach ([
                    ['Apply', 'Fill in the short form below. Attach any documents you have — they help, but they are not required.'],
                    ['Review', 'Our case team reviews every application in confidence and may call you to learn more.'],
                    ['Support', 'If approved, we agree a support plan with you and stay in touch as it is delivered.'],
                ] as $i => [$step, $copy])
                    <li class="reveal rounded-3xl bg-cream-100 p-7 ring-1 ring-brand-100" style="transition-delay: {{ $i * 120 }}ms">
                        <span class="grid size-10 place-items-center rounded-full bg-gold-500 font-serif text-lg font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-serif text-xl text-heading">{{ $step }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $copy }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <div id="apply" class="bg-cream-50">
        <livewire:site.beneficiary-application-form />
    </div>

    <section class="bg-brand-900 py-16 text-white">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 text-center sm:px-6 lg:flex-row lg:text-left lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-300">Applying for someone else?</p>
                <h2 class="mt-2 font-serif text-2xl sm:text-3xl">Talk to us first.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80">
                    Community leaders, teachers and health workers can refer families to us. Get in touch and we'll guide you through the process.
                </p>
            </div>
            <a href="{{ route('contact') }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-surface hover:text-heading" wire:navigate>
                Contact the team
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>
</x-layouts::site>
