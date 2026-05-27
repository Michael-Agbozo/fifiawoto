<?php

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Mail\VolunteerApplicationReceived;
use App\Models\VolunteerApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
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

    public ?string $contextEventTitle = null;

    public function mount(bool $compact = false, ?string $contextEventTitle = null): void
    {
        $this->compact = $compact;
        $this->contextEventTitle = $contextEventTitle;
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
            $this->addError('rate', "Too many submissions. Please wait {$seconds} seconds and try again.");

            return;
        }

        $data = $this->validate();

        RateLimiter::hit($key, 60);

        $application = VolunteerApplication::query()->create([
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
            ->send(new VolunteerApplicationReceived($application));

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
        <section class="py-20" aria-labelledby="volunteer-form-heading">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl border border-brand-100 bg-white p-8 shadow-sm sm:p-10">
                    <div class="text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Application</p>
                        <h2 id="volunteer-form-heading" class="mt-3 font-serif text-3xl text-brand-900 sm:text-4xl">Apply to Volunteer</h2>
                        <p class="mt-3 text-sm text-ink-500">
                            Tell us a little about yourself and how you'd like to contribute. Our volunteer coordinator will be in touch.
                        </p>
                    </div>
                    @include('components.site.partials.volunteer-form-body')
                </div>
            </div>
        </section>
    @else
        @if ($contextEventTitle)
            <p class="mb-5 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">
                Volunteering for: <strong class="font-semibold text-brand-900">{{ $contextEventTitle }}</strong>
            </p>
        @endif
        @include('components.site.partials.volunteer-form-body')
    @endif
</div>
