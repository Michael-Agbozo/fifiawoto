<?php

use App\Concerns\DetectsSpamSubmissions;
use App\Enums\AssistanceType;
use App\Enums\BeneficiaryApplicationStatus;
use App\Mail\BeneficiaryApplicationReceived;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use DetectsSpamSubmissions;
    use WithFileUploads;

    /** Maximum number of supporting documents per application. */
    public const MAX_DOCUMENTS = 5;

    #[Validate('required|string|max:120')]
    public string $full_name = '';

    #[Validate('required|string|max:40')]
    public string $phone = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:120')]
    public string $country = '';

    #[Validate('nullable|string|max:120')]
    public string $region = '';

    public string $assistance_type = '';

    #[Validate('required|string|min:30|max:3000')]
    public string $situation = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $documents = [];

    #[Validate('accepted')]
    public bool $consent = false;

    public bool $submitted = false;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'assistance_type' => ['required', Rule::enum(AssistanceType::class)],
            'documents' => ['array', 'max:'.self::MAX_DOCUMENTS],
            'documents.*' => ['file', 'max:10240', 'mimes:'.implode(',', BeneficiaryDocument::ALLOWED_EXTENSIONS)],
        ];
    }

    public function removeDocument(int $index): void
    {
        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
    }

    public function submit(): void
    {
        $key = 'public-form:beneficiary-application:'.(request()->ip() ?? 'anon');

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('rate', "Too many submissions. Please wait {$seconds} seconds and try again.");

            return;
        }

        if ($this->isLikelySpam()) {
            RateLimiter::hit($key, 60);
            $this->reset([
                'full_name', 'phone', 'email', 'country', 'region',
                'assistance_type', 'situation', 'documents', 'consent', 'website',
            ]);
            $this->submitted = true;

            return;
        }

        $data = $this->validate();

        RateLimiter::hit($key, 60);

        $storedPaths = [];

        try {
            $application = DB::transaction(function () use ($data, &$storedPaths): BeneficiaryApplication {
                $application = new BeneficiaryApplication([
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?: null,
                    'country' => $data['country'],
                    'region' => $data['region'] ?: null,
                    'assistance_type' => $data['assistance_type'],
                    'situation' => $data['situation'],
                    'status' => BeneficiaryApplicationStatus::New->value,
                ]);
                $application->forceFill(['consented_at' => now()])->save();

                foreach ($this->documents as $upload) {
                    $path = $upload->store("applications/{$application->id}", 'beneficiaries');
                    $storedPaths[] = $path;

                    $application->documents()->create([
                        'disk' => 'beneficiaries',
                        'path' => $path,
                        'original_name' => $upload->getClientOriginalName(),
                        'mime_type' => $upload->getMimeType() ?: 'application/octet-stream',
                        'size_bytes' => $upload->getSize() ?: 0,
                    ]);
                }

                return $application;
            });
        } catch (Throwable $e) {
            Storage::disk('beneficiaries')->delete($storedPaths);

            throw $e;
        }

        Mail::to(config('notifications.admin_email'))
            ->queue(new BeneficiaryApplicationReceived($application));

        $this->reset([
            'full_name', 'phone', 'email', 'country', 'region',
            'assistance_type', 'situation', 'documents', 'consent',
        ]);
        $this->submitted = true;
    }

    public function with(): array
    {
        return [
            'assistanceOptions' => AssistanceType::options(),
            'maxDocuments' => self::MAX_DOCUMENTS,
        ];
    }
}; ?>

<section class="py-20" aria-labelledby="support-form-heading">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-brand-100 bg-surface p-8 shadow-sm sm:p-10">
            <div class="text-center">
                <p class="eyebrow">Application</p>
                <h2 id="support-form-heading" class="section-title text-heading mt-3">Apply for Support</h2>
                <p class="mt-3 text-sm text-ink-500">
                    Tell us about your situation. Everything you share is kept confidential and seen only by our case team.
                </p>
            </div>

            @if ($submitted)
                <div role="status" class="mt-8 rounded-2xl border border-brand-200 bg-brand-50 p-6 text-sm text-heading-soft">
                    <p class="font-semibold text-heading">Thank you — your application has been received.</p>
                    <p class="mt-1 text-ink-500">
                        A member of our team will review it and contact you by phone or email. Reviews usually take one to two weeks.
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
                            <label for="sup-name" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Full name</label>
                            <input id="sup-name" type="text" wire:model="full_name" required autocomplete="name"
                                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @error('full_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="sup-phone" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Phone number</label>
                            <input id="sup-phone" type="tel" wire:model="phone" required autocomplete="tel"
                                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sup-email" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Email address <span class="normal-case tracking-normal text-ink-500">(optional)</span></label>
                            <input id="sup-email" type="email" wire:model="email" autocomplete="email"
                                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="sup-country" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Country</label>
                            <input id="sup-country" type="text" wire:model="country" required autocomplete="country-name"
                                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="sup-region" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Region / town <span class="normal-case tracking-normal text-ink-500">(optional)</span></label>
                            <input id="sup-region" type="text" wire:model="region"
                                class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @error('region') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <fieldset>
                        <legend class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Type of support needed</legend>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($assistanceOptions as $value => $label)
                                <label class="cursor-pointer rounded-full border border-brand-200 bg-cream-50 px-4 py-2 text-sm text-heading-soft has-checked:border-brand-700 has-checked:bg-brand-700 has-checked:text-paper">
                                    <input type="radio" name="assistance_type" value="{{ $value }}" wire:model.live="assistance_type" class="sr-only">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('assistance_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </fieldset>

                    <div>
                        <label for="sup-situation" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Your situation</label>
                        <textarea id="sup-situation" wire:model="situation" rows="5" required
                            class="mt-2 w-full rounded-md border border-brand-200 bg-cream-50 px-3 py-2.5 text-sm text-heading focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            placeholder="Briefly describe your circumstances and how the foundation could help."></textarea>
                        @error('situation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="sup-documents" class="block text-xs font-semibold uppercase tracking-[0.22em] text-label">Supporting documents <span class="normal-case tracking-normal text-ink-500">(optional)</span></label>
                        <p class="mt-1 text-xs text-ink-500">ID, medical or school records — up to {{ $maxDocuments }} files, 10 MB each (PDF, images, Word, Excel).</p>
                        <input id="sup-documents" type="file" wire:model="documents" multiple
                            accept=".{{ implode(',.', \App\Models\BeneficiaryDocument::ALLOWED_EXTENSIONS) }}"
                            class="mt-2 block w-full text-sm text-ink-700 file:mr-3 file:rounded-full file:border-0 file:bg-gold-500 file:px-4 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-brand-900">
                        <p class="mt-1 text-xs text-ink-500" wire:loading wire:target="documents">Uploading…</p>
                        @error('documents') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @foreach ($errors->get('documents.*') as $messages)
                            <p class="mt-1 text-xs text-red-600">{{ $messages[0] }}</p>
                        @endforeach

                        @if ($documents)
                            <ul class="mt-3 divide-y divide-brand-100 rounded-lg border border-brand-100 bg-cream-50 text-sm">
                                @foreach ($documents as $index => $document)
                                    <li class="flex items-center justify-between gap-3 px-3 py-2" wire:key="sup-doc-{{ $index }}">
                                        <span class="truncate text-heading">{{ $document->getClientOriginalName() }}</span>
                                        <button type="button" wire:click="removeDocument({{ $index }})" class="shrink-0 text-xs font-semibold text-red-600 hover:text-red-700">Remove</button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <label class="flex items-start gap-3 text-sm text-heading-soft">
                        <input type="checkbox" wire:model.live="consent" class="mt-1 rounded border-brand-300 text-label focus:ring-brand-500" required>
                        <span>
                            I consent to the foundation securely storing this information and any documents to assess my application and contact me about support.
                            See our <a href="{{ route('legal.privacy') }}" class="font-semibold text-gold-500 hover:text-heading" wire:navigate>privacy policy</a>.
                        </span>
                    </label>
                    @error('consent') <p class="-mt-3 text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="pt-2">
                        <button type="submit"
                            class="btn btn-primary disabled:opacity-60"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submit">Submit application</span>
                            <span wire:loading wire:target="submit">Submitting…</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
