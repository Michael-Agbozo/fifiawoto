<x-layouts::site title="Donate">
    <x-site.page-hero
        title="Your gift, their new beginning."
        breadcrumb="Donate"
        image="images/foundation/school-donation.jpg"
        alt="Children receiving school supplies through a Fifiawoto Foundation outreach"
    >
        Every contribution goes directly into the foundation's programmes — scholarships, medical care, women's livelihood support, and community outreach across Ghana, Togo, Benin, and the United States.
    </x-site.page-hero>

    {{-- IMPACT TIERS — concrete examples of what a gift funds --}}
    <section class="py-20 lg:py-24" aria-labelledby="impact-tiers-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Where your gift goes</p>
                <h2 id="impact-tiers-heading" class="reveal mt-3 font-serif text-3xl text-brand-900 sm:text-4xl" style="transition-delay: 120ms">Pick the impact that feels right</h2>
                <p class="reveal mt-4 text-sm leading-relaxed text-ink-500" style="transition-delay: 240ms">
                    Every amount makes a real difference. These are the concrete programmes a gift of each size can fund.
                </p>
            </div>

            <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['amount' => '$25',  'label' => 'A child\'s term', 'copy' => 'Notebooks, exercise books, and uniform basics for one child for a school term.', 'icon' => 'm12 6.5-9 4 9 4 9-4-9-4Zm0 8.5L4 11.5v4l8 3.5 8-3.5v-4l-8 3.5Z'],
                    ['amount' => '$60',  'label' => 'A health check',  'copy' => 'Mobile-clinic consultation, basic medication, and follow-up for a family of four.', 'icon' => 'M19.5 12.572 12 20l-7.5-7.428a5 5 0 1 1 7.5-6.566 5 5 0 1 1 7.5 6.566Z'],
                    ['amount' => '$150', 'label' => 'A widow\'s start', 'copy' => 'Seed capital and training for a widow joining the livelihood empowerment programme.',  'icon' => 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm-4 7c-4.4 0-8 2.7-8 6v2h16v-2c0-3.3-3.6-6-8-6Z'],
                    ['amount' => '$500', 'label' => 'A whole programme',  'copy' => 'Sponsors a single outreach event in one community — staff, supplies, and transport.',     'icon' => 'M3.75 6h16.5v3H3.75V6Zm0 4.5h16.5v3H3.75v-3Zm0 4.5h16.5v3H3.75v-3Z'],
                ] as $i => $tier)
                    <article class="reveal flex h-full flex-col rounded-3xl bg-white p-6 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100 transition hover:-translate-y-1 hover:shadow-[0_18px_50px_-20px_rgba(0,4,78,0.25)] hover:ring-brand-200" style="transition-delay: {{ $i * 100 }}ms">
                        <div class="grid size-12 place-items-center rounded-xl bg-gold-500/10 text-gold-500">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tier['icon'] }}"/></svg>
                        </div>
                        <p class="mt-5 font-serif text-3xl font-bold text-brand-900">{{ $tier['amount'] }}</p>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-gold-500">{{ $tier['label'] }}</p>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-ink-500">{{ $tier['copy'] }}</p>
                    </article>
                @endforeach
            </div>

            <p class="reveal mx-auto mt-10 max-w-2xl text-center text-sm text-ink-500" style="transition-delay: 240ms">
                Other amount in mind? Every gift — large or small — extends the foundation's reach.
                <a href="{{ route('contact') }}" class="font-semibold text-brand-700 hover:text-gold-500" wire:navigate>Tell us what you'd like to give →</a>
            </p>
        </div>
    </section>

    {{-- ACTIVE FUNDRAISING EVENT (if any) --}}
    @if ($featuredEvent)
        @php
            $raised = $featuredEvent->raisedCents();
            $goal = (int) $featuredEvent->goal_cents;
            $percent = $featuredEvent->progressPercent();
        @endphp
        <section class="bg-brand-900 py-20 text-white lg:py-24" aria-labelledby="active-event-heading">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:gap-16 lg:px-8">
                <div>
                    <p class="reveal-down text-xs font-semibold uppercase tracking-[0.32em] text-gold-400">Active campaign</p>
                    <h2 id="active-event-heading" class="reveal mt-4 font-serif text-3xl font-bold leading-tight sm:text-4xl lg:text-[44px]" style="transition-delay: 120ms">
                        {{ $featuredEvent->title }}
                    </h2>
                    <p class="reveal mt-5 text-sm leading-relaxed text-white/85 sm:text-base" style="transition-delay: 240ms">
                        {{ \Illuminate\Support\Str::limit(strip_tags($featuredEvent->description), 220) }}
                    </p>
                    <div class="reveal mt-6 flex flex-wrap gap-3" style="transition-delay: 360ms">
                        <a href="{{ route('events.show', $featuredEvent) }}" class="inline-flex items-center gap-1.5 rounded-full bg-gold-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white hover:text-brand-900" wire:navigate>
                            See the event
                            <span aria-hidden="true">→</span>
                        </a>
                        <a href="#how-to-give" class="inline-flex items-center gap-1.5 rounded-full border-2 border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:border-white hover:bg-white hover:text-brand-900">
                            Donate to this campaign
                        </a>
                    </div>
                </div>

                <div class="reveal-right rounded-3xl border border-white/15 bg-white/5 p-7 backdrop-blur sm:p-8" style="transition-delay: 240ms">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold-300">Progress</p>
                    <div class="mt-4 flex items-baseline justify-between gap-4">
                        <p class="font-serif text-4xl font-bold sm:text-5xl">${{ number_format($raised / 100) }}</p>
                        <p class="text-sm font-semibold text-white/70">of ${{ number_format($goal / 100) }} goal</p>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/15">
                        <div class="h-full rounded-full bg-gold-500 transition-all duration-700" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="mt-3 text-xs text-white/65">{{ $percent }}% of the goal is funded. Every gift moves us closer.</p>
                </div>
            </div>
        </section>
    @endif

    {{-- HOW TO GIVE — payment methods --}}
    <section id="how-to-give" class="bg-cream-50 py-20 lg:py-24" aria-labelledby="how-to-give-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Ways to give</p>
                <h2 id="how-to-give-heading" class="reveal mt-3 font-serif text-3xl text-brand-900 sm:text-4xl" style="transition-delay: 120ms">Choose the channel that suits you</h2>
                <p class="reveal mt-4 text-sm leading-relaxed text-ink-500" style="transition-delay: 240ms">
                    Online card payments are coming soon. In the meantime the foundation accepts mobile money, bank transfers, and in-person gifts. Every channel is logged and acknowledged.
                </p>
            </div>

            <div class="mt-14 grid gap-5 lg:grid-cols-3">
                {{-- Mobile Money --}}
                <article class="reveal flex h-full flex-col rounded-3xl bg-white p-7 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-xl bg-gold-500/10 text-gold-500">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="2.5" width="14" height="19" rx="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M11 18h2"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gold-500">Fastest</p>
                            <h3 class="font-serif text-xl font-bold text-brand-900">Mobile Money</h3>
                        </div>
                    </div>
                    <dl class="mt-5 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Network</dt>
                            <dd class="mt-1 text-ink-900">MTN MoMo · Ghana</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Number</dt>
                            <dd class="mt-1 font-mono text-base font-semibold text-brand-900">+233 XX XXX XXXX</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Reference</dt>
                            <dd class="mt-1 text-ink-700">Your name + "Donation"</dd>
                        </div>
                    </dl>
                    <p class="mt-5 text-xs text-ink-500">Send a screenshot to <a href="mailto:{{ config('notifications.admin_email') }}" class="font-semibold text-brand-700 hover:text-gold-500">{{ config('notifications.admin_email') }}</a> so we can confirm and acknowledge your gift.</p>
                </article>

                {{-- Bank Transfer --}}
                <article class="reveal flex h-full flex-col rounded-3xl bg-white p-7 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100" style="transition-delay: 100ms">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-xl bg-gold-500/10 text-gold-500">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-5 9 5M5 9v10h14V9M9 19v-7m3 7v-7m3 7v-7M3 9h18"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gold-500">International</p>
                            <h3 class="font-serif text-xl font-bold text-brand-900">Bank transfer</h3>
                        </div>
                    </div>
                    <dl class="mt-5 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Beneficiary</dt>
                            <dd class="mt-1 text-ink-900">Dadaa Fifiawoto Nyamadi Foundation</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">Bank</dt>
                            <dd class="mt-1 text-ink-900">Available on request</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-500">SWIFT / details</dt>
                            <dd class="mt-1 text-ink-700">Request via the contact form so we can share the full set securely.</dd>
                        </div>
                    </dl>
                    <a href="{{ route('contact') }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-gold-500" wire:navigate>
                        Request bank details
                        <span aria-hidden="true">→</span>
                    </a>
                </article>

                {{-- Card / Online — coming soon --}}
                <article class="reveal flex h-full flex-col rounded-3xl bg-white p-7 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)] ring-1 ring-brand-100" style="transition-delay: 200ms">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-xl bg-brand-100 text-brand-700">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2.5" y="6" width="19" height="12" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 10h19"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Coming soon</p>
                            <h3 class="font-serif text-xl font-bold text-brand-900">Card / online</h3>
                        </div>
                    </div>
                    <p class="mt-5 text-sm leading-relaxed text-ink-500">
                        We're finalising a secure online payment processor for card and Apple/Google Pay. In the meantime, reach out and we'll arrange a one-time or recurring gift in the channel that's easiest for you.
                    </p>
                    <a href="{{ route('contact') }}" class="mt-auto inline-flex items-center gap-1.5 pt-5 text-sm font-semibold text-brand-700 hover:text-gold-500" wire:navigate>
                        Get notified when it's live
                        <span aria-hidden="true">→</span>
                    </a>
                </article>
            </div>
        </div>
    </section>

    {{-- TRUST + RECURRING --}}
    <section class="py-20 lg:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8">
            <div class="reveal-left">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Stewardship</p>
                <h2 class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl">Where your money actually goes</h2>
                <p class="mt-5 text-sm leading-relaxed text-ink-500 sm:text-base">
                    The foundation is run by a small team of staff and volunteers. The board oversees how each gift is spent and a summary is shared with donors at the end of every programme. We don't pass funds through intermediaries.
                </p>
                <ul class="mt-7 space-y-3 text-sm">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-gold-500/15 text-gold-500">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <span class="text-ink-700"><strong class="text-ink-900">100% of restricted gifts</strong> go to the programme you nominate.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-gold-500/15 text-gold-500">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <span class="text-ink-700">Every gift is <strong class="text-ink-900">logged, acknowledged, and receipted</strong> within a week.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-gold-500/15 text-gold-500">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <span class="text-ink-700"><strong class="text-ink-900">Annual impact report</strong> shared with every donor on the giving list.</span>
                    </li>
                </ul>
            </div>

            <div class="reveal-right rounded-3xl bg-cream-100 p-8 ring-1 ring-brand-100 sm:p-10" style="transition-delay: 160ms">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gold-500">Monthly giving</p>
                <h3 class="mt-3 font-serif text-2xl text-brand-900 sm:text-3xl">Become a monthly partner</h3>
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    Recurring gifts are the most powerful way to support the foundation's programmes — they let the team plan ahead and commit to longer-term care for families.
                </p>
                <p class="mt-4 text-sm leading-relaxed text-ink-500">
                    Tell us a comfortable monthly amount; we'll send back the setup details for whichever channel suits you.
                </p>
                <a href="{{ route('contact') }}" class="mt-6 inline-flex items-center gap-1.5 rounded-2xl bg-brand-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-gold-500" wire:navigate>
                    Start monthly giving
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    {{-- CONTACT REASSURANCE --}}
    <section class="bg-brand-900 py-16 text-white">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 px-4 text-center sm:px-6 lg:flex-row lg:text-left lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-300">Questions?</p>
                <h2 class="mt-2 font-serif text-2xl sm:text-3xl">We're happy to walk you through any of this.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80">
                    Email <a href="mailto:{{ config('notifications.admin_email') }}" class="font-semibold text-gold-300 hover:text-white">{{ config('notifications.admin_email') }}</a> or use the contact form. We typically reply within one working day.
                </p>
            </div>
            <a href="{{ route('contact') }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-white hover:text-brand-900" wire:navigate>
                Contact the foundation
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <livewire:site.newsletter-signup source="donate" />
</x-layouts::site>
