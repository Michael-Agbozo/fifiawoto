<?php

use App\Concerns\DetectsSpamSubmissions;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Mail\VolunteerApplicationReceived;
use App\Models\Event;
use App\Models\VolunteerApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    use DetectsSpamSubmissions;

    #[Validate('required|string|max:120')]
    public string $full_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:40')]
    public string $phone = '';

    #[Validate('required|string|max:120')]
    public string $country = '';

    /** @var array<int, string> */
    public array $interests = [];

    public string $availability = '';

    #[Validate('nullable|string|max:1000')]
    public string $skills = '';

    #[Validate('required|string|min:30|max:2000')]
    public string $motivation = '';

    #[Validate('accepted')]
    public bool $consent = false;

    public bool $submitted = false;

    public bool $compact = false;

    #[Locked]
    public ?int $eventId = null;

    #[Locked]
    public ?string $contextEventTitle = null;

    public function mount(bool $compact = false, ?int $eventId = null): void
    {
        $this->compact = $compact;

        $event = $eventId ? Event::query()->published()->find($eventId) : null;
        $this->eventId = $event?->id;
        $this->contextEventTitle = $event?->title;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $interestValues = collect(VolunteerInterest::cases())->pluck('value')->implode(',');
        $availabilityValues = collect(VolunteerAvailability::cases())->pluck('value')->implode(',');

        return [
            'interests' => ['required', 'array', 'min:1'],
            'interests.*' => ['string', 'in:'.$interestValues],
            'availability' => ['required', 'string', 'in:'.$availabilityValues],
        ];
    }

    public function submit(): void
    {
        $key = 'public-form:volunteer:'.(request()->ip() ?? 'anon');

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('rate', __('Too many submissions. Please wait :v1 seconds and try again.', ['v1' => $seconds]));

            return;
        }

        if ($this->isLikelySpam()) {
            RateLimiter::hit($key, 60);
            $this->reset([
                'full_name', 'email', 'phone', 'country', 'interests',
                'availability', 'skills', 'motivation', 'consent', 'website',
            ]);
            $this->submitted = true;

            return;
        }

        $data = $this->validate();

        RateLimiter::hit($key, 60);

        $application = VolunteerApplication::query()->create([
            'event_id' => $this->eventId ? Event::query()->published()->find($this->eventId)?->id : null,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'interests' => $data['interests'],
            'availability' => $data['availability'],
            'skills' => $data['skills'] ?: null,
            'motivation' => $data['motivation'],
            'consented_at' => now(),
            'status' => VolunteerApplicationStatus::New->value,
        ]);

        Mail::to(config('notifications.admin_email'))
            ->queue(new VolunteerApplicationReceived($application));

        \App\Support\SiteAnalytics::conversion('volunteer_application');

        $this->reset([
            'full_name', 'email', 'phone', 'country', 'interests',
            'availability', 'skills', 'motivation', 'consent',
        ]);
        $this->submitted = true;
    }

    public function with(): array
    {
        return [
            'interestOptions' => VolunteerInterest::options(),
            'availabilityOptions' => VolunteerAvailability::options(),
        ];
    }
}; ?>

<div>
    @if (! $compact)
        <section class="section" aria-labelledby="volunteer-form-heading">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,8fr)] lg:gap-8 lg:px-8">
                {{-- What happens next --}}
                <aside class="relative isolate flex flex-col overflow-hidden rounded-3xl bg-brand-900 p-8 text-white sm:p-10">
                    <img src="{{ asset('images/foundation/outreach-4.webp') }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover opacity-30" loading="lazy">
                    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-900/70 via-brand-900/85 to-brand-900" aria-hidden="true"></div>

                    <p class="eyebrow eyebrow-light">{{ __('Volunteer with us') }}</p>
                    <h3 class="mt-3 font-serif text-3xl font-bold leading-tight text-white sm:text-4xl">{{ __('What happens next') }}</h3>

                    <ol class="mt-8 space-y-6">
                        @foreach ([
                            ['Send your application', 'Tell us about yourself, your interests and when you are free.'],
                            ['We get in touch', 'Our volunteer coordinator reaches out within a few business days.'],
                            ['Join an outreach', 'We match you with an event or programme that fits your skills.'],
                        ] as $index => [$step, $copy])
                            <li class="flex gap-4">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-white/10 font-serif text-lg font-bold text-white ring-1 ring-white/25">{{ $index + 1 }}</span>
                                <div>
                                    <p class="font-semibold text-white">{{ __($step) }}</p>
                                    <p class="mt-1 text-sm leading-relaxed text-white/75">{{ __($copy) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-auto pt-10">
                        <div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                            <p class="text-sm text-white/75">{{ __('Questions before you apply?') }}</p>
                            <a href="mailto:hello@fifiawoto.org" class="mt-1 inline-block font-semibold text-white hover:text-gold-300">{{ __('hello@fifiawoto.org') }}</a>
                        </div>
                    </div>
                </aside>

                {{-- Form --}}
                <div class="card p-6 shadow-[0_24px_60px_-40px_rgba(0,4,78,0.45)] sm:p-10">
                    <p class="eyebrow">{{ __('Application') }}</p>
                    <h2 id="volunteer-form-heading" class="section-title text-heading mt-3">{{ __('Apply to Volunteer') }}</h2>
                    <p class="mt-3 max-w-2xl text-base leading-relaxed text-ink-500">
                        {{ __('Tell us a little about yourself and how you\'d like to contribute. Our volunteer coordinator will be in touch.') }}
                    </p>
                    @include('components.site.partials.volunteer-form-body')
                </div>
            </div>
        </section>
    @else
        @if ($contextEventTitle)
            <p class="mb-5 rounded-xl bg-brand-50 px-4 py-3 text-sm text-heading-soft">
                {{ __('Volunteering for:') }} <strong class="font-semibold text-heading">{{ __($contextEventTitle) }}</strong>
            </p>
        @endif
        @include('components.site.partials.volunteer-form-body')
    @endif
</div>
