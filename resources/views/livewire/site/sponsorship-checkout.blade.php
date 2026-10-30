<div>
    @if ($step === 'complete')
        <section class="mx-auto max-w-3xl px-6 py-20 text-center sm:py-28" aria-live="polite">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-brand-50 text-3xl text-label" aria-hidden="true">✓</span>
            <p class="mt-7 text-xs font-semibold uppercase tracking-[0.25em] text-gold-500">Thank you for caring</p>
            <h1 class="mt-4 text-4xl text-heading sm:text-5xl">Sponsorship request received</h1>
            <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-ink-500">Our team will follow up with you about your request and the next steps. No payment has been taken and no recurring charge has been set up.</p>
            <a href="{{ route('sponsor-a-child.show', $child->public_sponsorship_slug) }}" class="mt-9 inline-flex rounded-full bg-gold-500 px-7 py-4 text-sm font-bold text-white hover:bg-heading hover:text-surface">Back to {{ $child->public_display_name }}</a>
            <a href="{{ route('sponsor-a-child') }}" class="mt-6 block text-sm font-semibold text-label underline underline-offset-4">Meet the children</a>
        </section>
    @else
        <div class="grid lg:grid-cols-2">
            <section class="min-w-0 px-6 py-10 sm:px-10 lg:px-12 lg:py-14 xl:px-16" aria-labelledby="information-heading">
                <a href="{{ route('sponsor-a-child.show', $child->public_sponsorship_slug) }}" class="text-sm font-semibold text-label underline-offset-4 hover:underline">← Back to {{ $child->public_display_name }}</a>
                <ol aria-label="Sponsorship request progress" class="mt-9 flex flex-wrap items-center gap-3 text-sm font-semibold sm:gap-5">
                    <li class="flex items-center gap-2 text-label" @if ($step === 'summary') aria-current="step" @endif><span class="flex size-7 items-center justify-center rounded-full bg-gold-500 text-white">1</span> Summary</li>
                    <li aria-hidden="true" class="text-brand-200">→</li>
                    <li @class(['flex items-center gap-2', 'text-label' => $step === 'review', 'text-ink-500' => $step !== 'review']) @if ($step === 'review') aria-current="step" @endif><span @class(['flex size-7 items-center justify-center rounded-full', 'bg-gold-500 text-white' => $step === 'review', 'bg-cream-200 text-ink-500' => $step !== 'review'])>2</span> Review &amp; submit</li>
                </ol>
                <h1 id="information-heading" class="mt-12 border-b border-cream-300 pb-7 text-4xl text-heading sm:text-5xl">{{ $step === 'review' ? 'Review your request' : 'Your information' }}</h1>
                @error('rate') <p role="alert" class="mt-5 rounded-xl border border-gold-500/40 bg-gold-500/10 p-4 text-sm text-gold-600">{{ $message }}</p> @enderror
                @error('review') <p role="alert" class="mt-5 rounded-xl border border-gold-500/40 bg-gold-500/10 p-4 text-sm text-gold-600">{{ $message }}</p> @enderror
                @if ($step === 'summary')
                    <form wire:submit="review" class="mt-9">
                        @include('components.site.partials.honeypot')
                        <h2 class="text-2xl text-heading">Contact information</h2>
                        <p class="mt-3 text-sm leading-6 text-ink-500">A few details so our team can help you take the next step. <span class="text-gold-500">*</span> Required</p>
                        <div class="mt-8 grid gap-x-5 gap-y-6 sm:grid-cols-2">
                            @foreach ([['firstName', 'First name', 'given-name', 'text', true], ['lastName', 'Last name', 'family-name', 'text', true], ['email', 'Email address', 'email', 'email', true], ['phone', 'Phone number', 'tel', 'tel', false], ['address', 'Mailing address', 'address-line1', 'text', false], ['addressLine2', 'Apartment, suite, etc.', 'address-line2', 'text', false], ['country', 'Country', 'country-name', 'text', true], ['city', 'City', 'address-level2', 'text', false], ['region', 'State / region', 'address-level1', 'text', false], ['postalCode', 'Postal code', 'postal-code', 'text', false]] as [$property, $label, $autocomplete, $type, $required])
                                <div @class(['min-w-0', 'sm:col-span-2' => in_array($property, ['address', 'addressLine2'])])>
                                    <label for="sponsor-{{ $property }}" class="block text-sm font-medium text-heading-soft">{{ $label }} @if ($required)<span class="text-gold-500">*</span>@endif</label>
                                    <input id="sponsor-{{ $property }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" wire:model="{{ $property }}" @required($required) @error($property) aria-invalid="true" aria-describedby="sponsor-{{ $property }}-error" @enderror class="mt-2 min-h-13 w-full rounded-lg border border-brand-200 bg-surface px-4 py-3 text-base text-heading shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/25">
                                    @error($property)<p id="sponsor-{{ $property }}-error" class="mt-2 text-sm text-gold-600">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                        <section aria-labelledby="mobile-pledge-heading" class="mt-8 rounded-xl border border-brand-100 bg-cream-100 p-5 lg:hidden">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 id="mobile-pledge-heading" class="font-sans text-base font-semibold text-heading">Your selected pledge</h3>
                                <a href="#donation-summary" class="shrink-0 text-sm font-semibold text-label underline underline-offset-4">Edit pledge</a>
                            </div>
                            <p class="mt-2 break-words text-sm text-heading-soft">For {{ $child->public_display_name }}</p>
                            <dl class="mt-4 space-y-3 text-sm text-heading-soft">
                                <div class="flex justify-between gap-4"><dt>Monthly pledge</dt><dd class="shrink-0 font-semibold tabular-nums">{{ \App\Support\Money::fromCents($monthlyCents, 2) }}</dd></div>
                                <div class="flex justify-between gap-4"><dt>One-time gift</dt><dd class="shrink-0 tabular-nums">{{ \App\Support\Money::fromCents($giftCents, 2) }}</dd></div>
                                <div class="flex justify-between gap-4 border-t border-brand-100 pt-3"><dt>First-month pledge</dt><dd class="shrink-0 font-semibold tabular-nums">{{ \App\Support\Money::fromCents($firstMonthCents, 2) }}</dd></div>
                                <div class="flex justify-between gap-4 font-bold text-heading"><dt>Due today</dt><dd class="shrink-0 tabular-nums">{{ \App\Support\Money::fromCents(0, 2) }}</dd></div>
                            </dl>
                            <p class="mt-4 text-xs leading-6 text-ink-500">No payment is taken. Our team will confirm your request.</p>
                        </section>
                        <button type="submit" wire:loading.attr="disabled" wire:target="review" class="mt-9 flex w-full items-center justify-center gap-3 rounded-full bg-gold-500 px-7 py-4 text-sm font-bold text-white transition hover:bg-heading hover:text-surface focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500 disabled:opacity-50"><span wire:loading.remove wire:target="review">Continue to review</span><span wire:loading wire:target="review">Checking your details…</span><span aria-hidden="true">→</span></button>
                        <p class="mt-4 text-center text-xs leading-6 text-ink-500">You can review everything before submitting. No payment is taken.</p>
                    </form>
                @else
                    <form wire:submit="submit" class="mt-9">
                        <div class="flex items-center justify-between gap-4"><h2 class="text-2xl text-heading">Your contact details</h2><button type="button" wire:click="edit" class="text-sm font-semibold text-label underline underline-offset-4">Edit details</button></div>
                        <dl class="mt-6 space-y-5 border-b border-cream-300 pb-7 text-sm">
                            <div><dt class="text-ink-500">Name</dt><dd class="mt-1 break-words font-semibold text-heading">{{ $firstName }} {{ $lastName }}</dd></div>
                            <div><dt class="text-ink-500">Email address</dt><dd class="mt-1 break-all font-semibold text-heading">{{ $email }}</dd></div>
                            @if ($phone)<div><dt class="text-ink-500">Phone</dt><dd class="mt-1 text-heading">{{ $phone }}</dd></div>@endif
                            <div><dt class="text-ink-500">Address</dt><dd class="mt-1 break-words leading-7 text-heading">{{ implode(', ', array_filter([$address, $addressLine2, $city, $region, $postalCode, $country])) }}</dd></div>
                        </dl>
                        <label class="mt-7 flex cursor-pointer items-start gap-3 text-sm leading-7 text-heading-soft"><input type="checkbox" wire:model="consent" class="mt-1.5 size-5 shrink-0 rounded border-brand-200 accent-heading"> <span>I agree that the foundation may contact me about this sponsorship request. I understand that my pledge is subject to confirmation and no payment will be taken today.</span></label>
                        @error('consent')<p role="alert" class="mt-2 text-sm text-gold-600">{{ $message }}</p>@enderror
                        <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="mt-8 w-full rounded-full bg-gold-500 px-7 py-4 text-sm font-bold text-white hover:bg-heading hover:text-surface focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500 disabled:opacity-50"><span wire:loading.remove wire:target="submit">Submit sponsorship request</span><span wire:loading wire:target="submit">Submitting your request…</span></button>
                    </form>
                @endif
            </section>
            <aside id="donation-summary" class="order-first scroll-mt-32 min-w-0 lg:order-last bg-cream-100 px-6 py-10 sm:px-10 lg:px-12 lg:py-14 xl:px-16" aria-labelledby="donation-heading">
                <div class="lg:sticky lg:top-32">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-ink-500">A brighter next chapter</p>
                    <h2 id="donation-heading" class="mt-4 text-4xl text-heading sm:text-5xl">Your donation</h2>
                    <div class="mt-8 overflow-hidden rounded-xl border border-cream-300 bg-surface shadow-sm">
                        <p class="bg-brand-50 px-5 py-3 text-xs font-semibold text-label">Your sponsorship request</p>
                        <div class="p-5 sm:p-6">
                            <div class="flex items-start gap-4">
                                <img src="{{ Storage::disk('public')->url($child->public_photo_path) }}" alt="{{ $child->public_display_name }}" class="size-20 shrink-0 rounded-full object-cover object-center">
                                <div class="min-w-0"><h3 class="break-words font-sans text-xl font-semibold text-heading">{{ $child->public_display_name }}</h3><p class="mt-2 text-sm text-ink-500">{{ $child->date_of_birth->age }} years · {{ $child->public_country }}</p></div>
                            </div>
                            @if (Str::endsWith($child->public_display_name, '(Demo)'))<p class="mt-4 text-xs leading-5 text-ink-500">Demo profile · Illustrative foundation photograph</p>@endif
                            @if ($step === 'summary')
                                <fieldset class="mt-7"><legend class="text-sm font-semibold text-heading">Choose your monthly pledge <span class="font-normal text-ink-500">({{ $currency }})</span></legend><p class="mt-2 text-xs leading-5 text-ink-500">Suggested support — choose an amount that works for you.</p>
                                    <div class="mt-4 grid grid-cols-3 gap-2">
                                        @foreach (['25', '50', '100'] as $amount)
                                            <label @class(['flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-2 py-4 text-sm font-semibold transition', 'border-heading bg-brand-50 text-label' => $monthlyAmount === $amount, 'border-cream-300 text-heading hover:border-brand-200' => $monthlyAmount !== $amount])><input type="radio" wire:model.live="monthlyAmount" value="{{ $amount }}" class="size-4 accent-heading"><span>{{ \App\Support\Money::format((int) $amount) }}</span></label>
                                        @endforeach
                                    </div>
                                    <label for="monthly-amount" class="mt-5 block text-sm text-heading-soft">Or enter your own monthly amount</label>
                                    <div class="relative mt-2"><span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-sm text-ink-500">{{ $currency }}</span><input id="monthly-amount" type="number" min="1" step="0.01" wire:model.live.debounce.400ms="monthlyAmount" class="min-h-12 w-full rounded-lg border border-brand-200 bg-surface py-3 pl-16 pr-4 text-heading focus:outline-none focus:ring-2 focus:ring-brand-500/25"></div>
                                    @error('monthlyAmount')<p role="alert" class="mt-2 text-sm text-gold-600">{{ $message }}</p>@enderror
                                </fieldset>
                            @else
                                <p class="mt-6 border-t border-cream-300 pt-5 text-lg font-semibold text-heading">{{ \App\Support\Money::fromCents($monthlyCents, 2) }} <span class="text-sm font-normal text-ink-500">/ month</span></p>
                            @endif
                        </div>
                        <div class="border-t border-cream-300 p-5 sm:p-6">
                            @if ($step === 'summary')
                                <label class="flex cursor-pointer items-start gap-3 text-sm font-semibold leading-6 text-heading"><input type="checkbox" wire:model.live="addGift" class="mt-0.5 size-5 shrink-0 rounded border-brand-200 accent-heading"><span>Add an optional one-time gift</span></label>
                                @if ($addGift)
                                    <label for="gift-amount" class="mt-4 block text-sm text-ink-500">Gift amount ({{ $currency }})</label><input id="gift-amount" type="number" min="1" step="0.01" wire:model.live.debounce.400ms="giftAmount" class="mt-2 min-h-12 w-full rounded-lg border border-brand-200 bg-surface px-4 py-3 text-heading focus:outline-none focus:ring-2 focus:ring-brand-500/25">
                                    @error('giftAmount')<p role="alert" class="mt-2 text-sm text-gold-600">{{ $message }}</p>@enderror
                                @endif
                            @else
                                <p class="text-sm text-ink-500">{{ $addGift ? 'Your request includes a one-time gift.' : 'No one-time gift added.' }}</p>
                            @endif
                        </div>
                    </div>
                    <dl class="mt-8 space-y-4 text-sm text-heading-soft">
                        <div class="flex items-baseline justify-between gap-4"><dt>Monthly pledge</dt><dd class="shrink-0 font-semibold tabular-nums">{{ \App\Support\Money::fromCents($monthlyCents, 2) }}</dd></div>
                        <div class="flex items-baseline justify-between gap-4"><dt>One-time gift</dt><dd class="shrink-0 tabular-nums">{{ \App\Support\Money::fromCents($giftCents, 2) }}</dd></div>
                        <div class="flex items-baseline justify-between gap-4 border-t border-cream-300 pt-4"><dt>First-month pledge</dt><dd class="shrink-0 font-semibold tabular-nums">{{ \App\Support\Money::fromCents($firstMonthCents, 2) }}</dd></div>
                        <div class="flex items-baseline justify-between gap-4 text-xl font-bold text-heading"><dt>Due today</dt><dd class="shrink-0 tabular-nums">{{ \App\Support\Money::fromCents(0, 2) }}</dd></div>
                    </dl>
                    <div class="mt-7 border-l-2 border-heading bg-surface px-5 py-4"><p class="text-sm font-semibold text-heading">A pledge today. A conversation next.</p><p class="mt-2 text-sm leading-6 text-ink-500">No payments are processed here yet. Our team will contact you to confirm your request and discuss payment arrangements. No recurring charge is set up.</p></div>
                    <p class="mt-6 text-xs leading-6 text-ink-500">Every connection is coordinated by the foundation, with the child’s wellbeing and family’s privacy at its heart.</p>
                </div>
            </aside>
        </div>
    @endif
</div>
