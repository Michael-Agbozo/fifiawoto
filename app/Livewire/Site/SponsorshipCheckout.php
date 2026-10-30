<?php

namespace App\Livewire\Site;

use App\Concerns\DetectsSpamSubmissions;
use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use App\Mail\ContactFormReceived;
use App\Models\Beneficiary;
use App\Models\ContactMessage;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SponsorshipCheckout extends Component
{
    use DetectsSpamSubmissions;

    #[Locked]
    public string $profile;

    #[Locked]
    public string $step = 'summary';

    #[Locked]
    public string $requestId;

    /** @var array<string, string|int|bool> */
    #[Locked]
    public array $reviewedDetails = [];

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $addressLine2 = '';

    public string $country = '';

    public string $city = '';

    public string $region = '';

    public string $postalCode = '';

    public string $monthlyAmount = '50';

    public bool $addGift = false;

    public string $giftAmount = '30';

    public bool $consent = false;

    public function mount(string $profile): void
    {
        $this->profile = $profile;
        $this->requestId = (string) Str::uuid();
        $this->child();
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $amountRules = ['required', 'string', 'regex:/^\d{1,5}(?:\.\d{1,2})?$/', 'numeric', 'min:1', 'max:10000'];

        return [
            'firstName' => ['required', 'string', 'max:60'],
            'lastName' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:180'],
            'addressLine2' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:80'],
            'postalCode' => ['nullable', 'string', 'max:30'],
            'monthlyAmount' => $amountRules,
            'addGift' => ['boolean'],
            'giftAmount' => ['exclude_unless:addGift,true', ...$amountRules],
        ];
    }

    public function review(): void
    {
        if ($this->step === 'complete') {
            return;
        }

        $this->child();
        $this->reviewedDetails = $this->validatedDetails();
        $this->resetErrorBag();
        $this->step = 'review';
    }

    public function edit(): void
    {
        if ($this->step === 'complete') {
            return;
        }

        $this->step = 'summary';
        $this->consent = false;
        $this->resetErrorBag();
    }

    public function submit(): void
    {
        if ($this->step === 'complete') {
            return;
        }

        $this->child();

        if ($this->step !== 'review') {
            $this->addError('review', 'Please review your details before submitting your request.');

            return;
        }

        $details = $this->validatedDetails();

        if ($details !== $this->reviewedDetails) {
            $this->step = 'summary';
            $this->addError('review', 'Your details have changed. Please review them again before submitting.');

            return;
        }

        $this->validate(['consent' => ['accepted']]);
        $rateKey = 'public-form:sponsorship-checkout:'.(request()->ip() ?? 'anon');

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            $this->addError('rate', 'Too many requests. Please wait a minute and try again.');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        if ($this->isLikelySpam()) {
            $this->step = 'complete';

            return;
        }

        $handled = Cache::lock('sponsorship-checkout:'.$this->requestId, 10)->get(function () use ($details): bool {
            $completedKey = 'sponsorship-checkout:completed:'.$this->requestId;

            if (Cache::has($completedKey)) {
                $this->step = 'complete';

                return true;
            }

            DB::transaction(function () use ($details): void {
                $child = Beneficiary::query()->publicSponsorship()->where('public_sponsorship_id', $this->profile)->lockForUpdate()->firstOrFail();
                $messageBody = implode("\n", [
                    'Pending sponsorship request — no payment taken or subscription created.',
                    'Child: '.$child->public_display_name.' ('.$child->public_sponsorship_id.')',
                    'Monthly pledge: '.$details['currency'].' '.number_format($details['monthly_cents'] / 100, 2, '.', ''),
                    'One-time gift: '.$details['currency'].' '.number_format($details['gift_cents'] / 100, 2, '.', ''),
                    'First-month pledge: '.$details['currency'].' '.number_format(($details['monthly_cents'] + $details['gift_cents']) / 100, 2, '.', ''),
                    'Country: '.$details['country'],
                    'Mailing address (optional): '.$details['address'],
                    'Address line 2: '.$details['addressLine2'],
                    'City: '.$details['city'],
                    'Region/state: '.$details['region'],
                    'Postal code: '.$details['postalCode'],
                ]);
                $name = trim($details['firstName'].' '.$details['lastName']);

                ContactMessage::query()->create([
                    'full_name' => $name,
                    'email' => $details['email'],
                    'phone' => $details['phone'] ?: null,
                    'subject' => ContactSubject::Sponsorship,
                    'message' => $messageBody,
                    'consented_at' => now(),
                    'status' => ContactMessageStatus::New,
                ]);

                Mail::to(config('notifications.admin_email'))->queue(
                    (new ContactFormReceived($name, $details['email'], $details['phone'], 'Sponsorship request', $messageBody))->afterCommit()
                );
            });

            Cache::put($completedKey, true, now()->addDay());
            $this->step = 'complete';

            return true;
        });

        if (! $handled) {
            $this->addError('rate', 'Your request is being processed. Please try again shortly.');
        }
    }

    public function render(): View
    {
        $monthlyCents = $this->amountInCents($this->monthlyAmount);
        $giftCents = $this->addGift ? $this->amountInCents($this->giftAmount) : 0;

        return view('livewire.site.sponsorship-checkout', [
            'child' => $this->child(),
            'monthlyCents' => $monthlyCents,
            'giftCents' => $giftCents,
            'firstMonthCents' => $monthlyCents + $giftCents,
            'currency' => Money::baseCurrency(),
        ]);
    }

    private function child(): Beneficiary
    {
        return Beneficiary::query()->publicSponsorship()->where('public_sponsorship_id', $this->profile)->firstOrFail();
    }

    /** @return array<string, string|int|bool> */
    private function validatedDetails(): array
    {
        $details = $this->validate();

        foreach ($details as $field => $value) {
            if (is_string($value)) {
                $details[$field] = trim($value);
            }
        }

        $details['email'] = Str::lower($details['email']);
        $details['monthly_cents'] = $this->amountInCents($details['monthlyAmount']);
        $details['gift_cents'] = $this->addGift ? $this->amountInCents($details['giftAmount']) : 0;
        $details['currency'] = Money::baseCurrency();

        return $details;
    }

    private function amountInCents(string $amount): int
    {
        if (! preg_match('/^\d{1,5}(?:\.\d{1,2})?$/', $amount)) {
            return 0;
        }

        [$major, $minor] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $major * 100) + (int) str_pad($minor, 2, '0');
    }
}
