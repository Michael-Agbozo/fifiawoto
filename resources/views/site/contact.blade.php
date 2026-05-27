<x-layouts::site title="Contact">
    <x-site.page-hero
        title="Get in touch"
        breadcrumb="Contact"
        image="images/foundation/community-1.jpg"
        alt="Community gathering hosted by the Fifiawoto Foundation"
    >
        Partner with us, ask a question, send a tip, or just say hello. Use the contact form below or one of the channels in the directory — we typically reply within one working day.
    </x-site.page-hero>

    {{-- DIRECTORY --}}
    <section class="py-20 lg:py-24" aria-labelledby="directory-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Reach the right team</p>
                <h2 id="directory-heading" class="reveal mt-3 font-serif text-3xl text-brand-900 sm:text-4xl" style="transition-delay: 120ms">Channels and offices</h2>
                <p class="reveal mt-4 text-sm leading-relaxed text-ink-500 sm:text-base" style="transition-delay: 240ms">
                    Most enquiries are routed through the address below. For media requests, partnerships, or country-specific questions, use the dedicated channels.
                </p>
            </div>

            <div class="mt-12 grid gap-5 md:grid-cols-3">
                @foreach ([
                    [
                        'label' => 'General enquiries',
                        'value' => config('notifications.admin_email'),
                        'href'  => 'mailto:'.config('notifications.admin_email'),
                        'note'  => 'Donations, volunteering, partnerships, programmes.',
                        'icon'  => 'M3 5.25 12 13l9-7.75M3 5.25v13.5A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V5.25M3 5.25A2.25 2.25 0 0 1 5.25 3h13.5A2.25 2.25 0 0 1 21 5.25',
                    ],
                    [
                        'label' => 'Press and media',
                        'value' => 'media@fifiawoto.org',
                        'href'  => 'mailto:media@fifiawoto.org',
                        'note'  => 'Story pitches, interview requests, photo and video.',
                        'icon'  => 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25',
                    ],
                    [
                        'label' => 'Phone',
                        'value' => '+233 (0) 00 000 0000',
                        'href'  => 'tel:+23300000000',
                        'note'  => 'Monday to Friday, 9 am – 5 pm GMT.',
                        'icon'  => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z',
                    ],
                ] as $i => $channel)
                    <a
                        href="{{ $channel['href'] }}"
                        class="reveal group flex h-full flex-col rounded-3xl bg-white p-6 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100 transition hover:-translate-y-1 hover:shadow-[0_18px_50px_-20px_rgba(0,4,78,0.25)] hover:ring-brand-200"
                        style="transition-delay: {{ $i * 100 }}ms"
                    >
                        <div class="flex items-center gap-3">
                            <span class="grid size-11 place-items-center rounded-xl bg-gold-500/10 text-gold-500 transition group-hover:bg-gold-500 group-hover:text-white">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $channel['icon'] }}"/></svg>
                            </span>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-500">{{ $channel['label'] }}</p>
                        </div>
                        <p class="mt-5 break-words font-serif text-lg font-bold text-brand-900 group-hover:text-gold-500">{{ $channel['value'] }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $channel['note'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- OFFICES --}}
    <section class="bg-cream-50 py-20 lg:py-24" aria-labelledby="offices-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Where we work</p>
                <h2 id="offices-heading" class="reveal mt-3 font-serif text-3xl text-brand-900 sm:text-4xl" style="transition-delay: 120ms">Foundation offices</h2>
                <p class="reveal mt-4 text-sm leading-relaxed text-ink-500 sm:text-base" style="transition-delay: 240ms">
                    Programme staff and volunteers operate across four countries. Drop in by appointment.
                </p>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    [
                        'country' => 'Ghana',
                        'city'    => 'Accra',
                        'address' => 'Foundation HQ · Accra, Greater Accra Region',
                        'role'    => 'Headquarters · programmes & operations',
                        'flag'    => '🇬🇭',
                    ],
                    [
                        'country' => 'Togo',
                        'city'    => 'Lomé',
                        'address' => 'Field office · Lomé, Maritime Region',
                        'role'    => 'Cross-border outreach & community partnerships',
                        'flag'    => '🇹🇬',
                    ],
                    [
                        'country' => 'Benin',
                        'city'    => 'Cotonou',
                        'address' => 'Field office · Cotonou, Littoral Department',
                        'role'    => 'Education & women\'s livelihood programmes',
                        'flag'    => '🇧🇯',
                    ],
                    [
                        'country' => 'United States',
                        'city'    => 'Diaspora office',
                        'address' => 'Mailing address available on request',
                        'role'    => 'Donor stewardship & diaspora engagement',
                        'flag'    => '🇺🇸',
                    ],
                ] as $i => $office)
                    <div class="reveal flex h-full flex-col rounded-3xl bg-white p-6 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100" style="transition-delay: {{ $i * 100 }}ms">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl" aria-hidden="true">{{ $office['flag'] }}</span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-500">{{ $office['country'] }}</p>
                                <p class="font-serif text-lg font-bold text-brand-900">{{ $office['city'] }}</p>
                            </div>
                        </div>
                        <p class="mt-5 text-sm font-medium text-ink-900">{{ $office['address'] }}</p>
                        <p class="mt-2 text-xs text-ink-500">{{ $office['role'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- RESPONSE TIME + FAQ --}}
    <section class="py-20 lg:py-24" aria-labelledby="response-heading">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_1.2fr] lg:items-start lg:gap-16 lg:px-8">
            <div class="reveal-left">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">What to expect</p>
                <h2 id="response-heading" class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl">A quick reply, every time.</h2>
                <p class="mt-5 text-sm leading-relaxed text-ink-500 sm:text-base">
                    A small team reads every message that comes in. We acknowledge new enquiries within 24 hours on weekdays — most get a real reply the same day.
                </p>
                <div class="mt-7 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-cream-100 p-5 ring-1 ring-brand-100">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-500">Typical response</p>
                        <p class="mt-2 font-serif text-2xl font-bold text-brand-900">&lt; 24 hours</p>
                    </div>
                    <div class="rounded-2xl bg-cream-100 p-5 ring-1 ring-brand-100">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-500">Weekly volume</p>
                        <p class="mt-2 font-serif text-2xl font-bold text-brand-900">
                            <span data-counter="40" data-suffix="+" data-duration="2800">0+</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="reveal-right space-y-3" style="transition-delay: 160ms" x-data="{ open: 0 }">
                @foreach ([
                    [
                        'Are you a registered charity?',
                        'Yes — the Dadaa Fifiawoto Nyamadi Foundation is a registered non-profit operating across Ghana, Togo, Benin, and the United States. We can share registration details on request.',
                    ],
                    [
                        'Can I donate goods instead of money?',
                        'Sometimes — it depends on the programme and current need. Reach out and let us know what you have; we\'ll either accept it or connect you with a partner who can.',
                    ],
                    [
                        'Do you offer internships or research placements?',
                        'We host a small number of placements every year for students and early-career professionals. Email the team with a short note about your interests and availability.',
                    ],
                    [
                        'How do I unsubscribe from newsletters?',
                        'Every newsletter has an unsubscribe link in the footer. You can also email the team and we\'ll remove you immediately.',
                    ],
                ] as $i => [$q, $a])
                    <details class="group rounded-2xl bg-white p-5 ring-1 ring-brand-100 transition open:shadow-[0_4px_20px_-10px_rgba(0,4,78,0.18)]">
                        <summary class="flex cursor-pointer items-start justify-between gap-4 text-left list-none">
                            <span class="font-serif text-base font-bold text-brand-900 sm:text-lg">{{ $q }}</span>
                            <svg class="mt-1 size-5 shrink-0 text-gold-500 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-ink-500">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CONTACT FORM (existing Livewire component) --}}
    <div id="contact-form" class="scroll-mt-24">
        <livewire:site.contact-form />
    </div>

    <livewire:site.newsletter-signup source="contact" />
</x-layouts::site>
