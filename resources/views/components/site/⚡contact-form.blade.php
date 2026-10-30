<?php

use App\Concerns\DetectsSpamSubmissions;
use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use App\Mail\ContactFormReceived;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    use DetectsSpamSubmissions;

    #[Validate('required|string|max:120')]
    public string $full_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:40')]
    public string $phone = '';

    public string $subject = '';

    #[Validate('required|string|min:15|max:4000')]
    public string $message = '';

    #[Validate('accepted')]
    public bool $consent = false;

    #[\Livewire\Attributes\Locked]
    public ?string $sponsorshipProfile = null;

    public bool $submitted = false;

    /** Pre-select the subject from links like /contact?subject=donation. */
    public function mount(?string $sponsorshipProfile = null): void
    {
        $this->sponsorshipProfile = $sponsorshipProfile;
        if ($sponsorshipProfile) {
            abort_unless(\App\Models\Beneficiary::query()->publicSponsorship()->where('public_sponsorship_id', $sponsorshipProfile)->exists(), 404);
        }
        $this->subject = ContactSubject::tryFrom((string) request()->query('subject'))?->value ?? '';
        if ($this->sponsorshipProfile) {
            $this->subject = ContactSubject::Sponsorship->value;
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $subjectValues = collect(ContactSubject::cases())->pluck('value')->implode(',');

        return [
            'subject' => ['required', 'string', 'in:'.$subjectValues],
        ];
    }

    public function submit(): void
    {
        if ($this->submitted) {
            return;
        }
        if ($this->sponsorshipProfile) {
            $child = \App\Models\Beneficiary::query()->publicSponsorship()->where('public_sponsorship_id', $this->sponsorshipProfile)->firstOrFail();
            $this->subject = ContactSubject::Sponsorship->value;
        }
        $key = 'public-form:contact:'.(request()->ip() ?? 'anon');

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('rate', "Too many submissions. Please wait {$seconds} seconds and try again.");

            return;
        }

        if ($this->isLikelySpam()) {
            RateLimiter::hit($key, 60);
            $this->reset(['full_name', 'email', 'phone', 'subject', 'message', 'consent', 'website']);
            $this->submitted = true;

            return;
        }

        $data = $this->validate();

        if (isset($child)) {
            $data['message'] = 'Sponsorship enquiry: '.$child->public_display_name.' ('.$child->public_sponsorship_id.")\n\n".$data['message'];
        }
        RateLimiter::hit($key, 60);

        ContactMessage::query()->create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'consented_at' => now(),
            'status' => ContactMessageStatus::New->value,
        ]);

        Mail::to(config('notifications.admin_email'))
            ->queue(new ContactFormReceived(
                senderName: $data['full_name'],
                senderEmail: $data['email'],
                senderPhone: $data['phone'] ?? '',
                subjectLine: ContactSubject::from($data['subject'])->label(),
                messageBody: $data['message'],
            ));

        $this->reset(['full_name', 'email', 'phone', 'subject', 'message', 'consent']);
        $this->submitted = true;
    }

    public function with(): array
    {
        return [
            'subjectOptions' => ContactSubject::options(),
            'sponsorshipName' => $this->sponsorshipProfile
                ? \App\Models\Beneficiary::query()->publicSponsorship()->where('public_sponsorship_id', $this->sponsorshipProfile)->firstOrFail()->public_display_name
                : null,
        ];
    }
}; ?>

<section class="py-16" aria-labelledby="contact-form-heading">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-brand-100 bg-surface p-8 shadow-sm sm:p-10">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Send a Message</p>
                <h2 id="contact-form-heading" class="mt-3 font-serif text-3xl text-heading sm:text-4xl">{{ $sponsorshipName ? 'Enquire about supporting '.$sponsorshipName : 'Contact the Foundation' }}</h2>
                <p class="mt-3 text-sm text-ink-500">
                    @if ($sponsorshipName)
                        Your enquiry is about {{ $sponsorshipName }}. Our team will help you explore sponsorship and explain the next steps.
                    @else
                        We welcome inquiries from individuals, organizations, and partners interested in supporting the mission.
                    @endif
                </p>
            </div>

            @if ($submitted)
                <div role="status" class="mt-8 rounded-2xl border border-brand-200 bg-brand-50 p-6 text-sm text-heading-soft">
                    <p class="font-semibold text-heading">Message received.</p>
                    <p class="mt-1 text-ink-500">
                        Thank you for reaching out. The team will follow up to discuss next steps. @if ($sponsorshipProfile) This enquiry does not confirm sponsorship or take a payment. @endif
                    </p>
                </div>
            @else
                <form wire:submit="submit" class="mt-8 space-y-5">
                    @include('components.site.partials.honeypot')
                    @error('rate')
                        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
                    @enderror
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Full name</label>
                            <input id="contact-name" type="text" wire:model="full_name" required
                                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contact-email" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Email address</label>
                            <input id="contact-email" type="email" wire:model="email" required
                                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contact-phone" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Phone number <span class="font-normal normal-case tracking-normal text-brand-500">(optional)</span></label>
                            <input id="contact-phone" type="tel" wire:model="phone"
                                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contact-subject" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Subject</label>
                            <div class="relative mt-2">
                                <select id="contact-subject" wire:model.live="subject" required
                                    class="w-full appearance-none rounded-md border border-brand-200 bg-cream-50 py-2.5 pl-3 pr-12 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 forced-colors:appearance-auto">
                                    <option value="">Select a subject…</option>
                                    @foreach ($subjectOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($subject === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <svg aria-hidden="true" class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-label forced-colors:hidden" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m5 7.5 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            </div>
                            @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="contact-message" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Message</label>
                        <textarea id="contact-message" wire:model="message" rows="5" required
                            class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            placeholder="Share your message, question, or proposal."></textarea>
                        @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-start gap-3 text-sm text-heading-soft">
                        <input type="checkbox" wire:model.live="consent" class="mt-1 rounded border-brand-300 text-label focus:ring-brand-500" required>
                        <span>I consent to the foundation contacting me at the details I have provided.</span>
                    </label>
                    @error('consent') <p class="-mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="pt-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-full bg-gold-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-900 disabled:opacity-60"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ $sponsorshipProfile ? 'Send sponsorship enquiry' : 'Send Message' }}</span>
                            <span wire:loading>Sending…</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
