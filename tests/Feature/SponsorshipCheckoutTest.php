<?php

use App\Enums\ContactSubject;
use App\Enums\SponsorshipStatus;
use App\Livewire\Site\SponsorshipCheckout;
use App\Mail\ContactFormReceived;
use App\Models\Beneficiary;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\Sponsorship;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function checkoutProfile(array $attributes = []): Beneficiary
{
    return Beneficiary::factory()->active()->create(array_merge([
        'date_of_birth' => today()->subYears(9),
        'public_display_name' => 'Ama',
        'public_story' => 'An approved public introduction.',
        'public_community_story' => 'An approved community introduction.',
        'public_country_story' => 'An approved country introduction.',
        'public_country' => 'Ghana',
        'public_photo_path' => 'sponsorship/approved.jpg',
        'guardian_consent_at' => now(),
        'guardian_consent_reference' => 'Approved guardian record',
        'sponsorship_published' => true,
    ], $attributes));
}

function filledSponsorshipCheckout(Beneficiary $child): Testable
{
    return Livewire::test(SponsorshipCheckout::class, ['profile' => $child->public_sponsorship_id])
        ->set('firstName', 'Akua')
        ->set('lastName', 'Donor')
        ->set('email', 'akua@example.test')
        ->set('country', 'Ghana');
}

it('uses unique stable public name slugs without exposing private names', function () {
    $child = checkoutProfile(['full_name' => 'PRIVATE NAME', 'public_display_name' => 'Ama (Demo)']);
    $other = checkoutProfile(['public_display_name' => 'Ama']);

    expect($child->public_sponsorship_slug)->toBe('ama')
        ->and($other->public_sponsorship_slug)->toStartWith('ama-');

    $child->update(['public_display_name' => 'A new public alias']);
    expect($child->fresh()->public_sponsorship_slug)->toBe('ama');

    $this->get('/sponsor-a-child/ama')->assertOk()->assertDontSee('PRIVATE NAME');
    $this->get('/sponsor-a-child/'.$child->public_sponsorship_id)
        ->assertStatus(301)->assertRedirect('/sponsor-a-child/ama');
});

it('shows inline profile tabs before a checkout link without the old enquiry form', function () {
    $child = checkoutProfile(['public_community_story' => '<script>private()</script> Safe community copy.']);

    $response = $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))
        ->assertOk()
        ->assertSeeInOrder(['Overview', 'Community', 'Country', 'Sponsor Ama'])
        ->assertSee('An approved country introduction.')
        ->assertSee('<script>private()</script> Safe community copy.')
        ->assertDontSee('<script>private()</script>', false)
        ->assertDontSee('Send sponsorship enquiry')
        ->assertDontSee('wire:submit="submit"', false)
        ->assertSee('href="'.route('sponsor-a-child.checkout', $child->public_sponsorship_slug).'"', false);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//*[@role="tab"]'))->toHaveCount(3)
        ->and($xpath->query('//*[@role="tabpanel"]'))->toHaveCount(3);
    $response->assertDontSee('Make a donation');
});

it('searches public names while combining existing filters and ignoring private case fields', function () {
    checkoutProfile(['full_name' => 'HiddenName', 'gender' => 'female']);
    checkoutProfile(['public_display_name' => 'Kofi', 'public_country' => 'Togo', 'gender' => 'male']);
    checkoutProfile(['public_display_name' => 'Ama hidden', 'sponsorship_published' => false]);

    $this->get(route('sponsor-a-child', ['search' => ' ama ', 'gender' => 'female', 'country' => 'Ghana', 'age' => '6-11']))
        ->assertOk()->assertSee('Meet Ama')->assertDontSee('Meet Kofi')->assertDontSee('Ama hidden');
    $this->get(route('sponsor-a-child', ['search' => 'HiddenName']))
        ->assertOk()->assertDontSee('Meet Ama')
        ->assertViewHas('children', fn ($children) => $children->total() === 0);
    $this->get(route('sponsor-a-child', ['search' => ['invalid']]))->assertRedirect();
});

it('renders a separate checkout with public child details and no payment inputs', function () {
    $child = checkoutProfile(['full_name' => 'PRIVATE NAME', 'phone' => 'PRIVATE PHONE']);

    $this->get(route('sponsor-a-child.checkout', $child->public_sponsorship_slug))
        ->assertOk()->assertSee('Your information')->assertSee('Your donation')
        ->assertSee('Ama')->assertSee('Ghana')->assertSee('Due today')->assertSee('$0.00')
        ->assertDontSee('PRIVATE NAME')->assertDontSee('PRIVATE PHONE')
        ->assertDontSee('Credit card')->assertDontSee('cardNumber');
});

it('requires valid donor details and a review before accepting any request', function () {
    $form = Livewire::test(SponsorshipCheckout::class, ['profile' => checkoutProfile()->public_sponsorship_id]);
    $form->call('submit')->assertHasErrors('review')->assertSet('step', 'summary');
    $form->call('review')->assertHasErrors(['firstName', 'lastName', 'email', 'country']);
    expect(ContactMessage::query()->count())->toBe(0);
});

it('keeps decimal amounts exact and saves only a pending request once', function () {
    Mail::fake();
    $child = checkoutProfile();
    $form = filledSponsorshipCheckout($child)
        ->set('monthlyAmount', '12.34')->set('addGift', true)->set('giftAmount', '5.67')
        ->assertSee('$18.01')->call('review')->assertHasNoErrors()->assertSet('step', 'review');
    $this->travel(5)->seconds();
    $form->set('consent', true)->call('submit')->assertHasNoErrors()->assertSet('step', 'complete')
        ->assertSee('Sponsorship request received')->assertSee('No payment has been taken');
    $form->call('submit');

    $form->instance()->step = 'review';
    $form->call('submit')->assertSet('step', 'complete');

    $message = ContactMessage::query()->sole();
    expect($message->subject)->toBe(ContactSubject::Sponsorship)
        ->and($message->message)->toContain('Monthly pledge: USD 12.34', 'One-time gift: USD 5.67', 'First-month pledge: USD 18.01', 'no payment taken')
        ->and($message->consented_at)->not->toBeNull()
        ->and(Donation::query()->count())->toBe(0);
    Mail::assertQueued(ContactFormReceived::class, 1);
});

it('requires consent and re-review if details change after review', function () {
    $form = filledSponsorshipCheckout(checkoutProfile())->call('review');
    $this->travel(5)->seconds();
    $form->call('submit')->assertHasErrors('consent');
    $form->set('email', 'changed@example.test')->set('consent', true)
        ->call('submit')->assertHasErrors('review')->assertSet('step', 'summary');
    expect(ContactMessage::query()->count())->toBe(0);
});

it('blocks invalid money values', function (string $amount) {
    filledSponsorshipCheckout(checkoutProfile())->set('monthlyAmount', $amount)
        ->call('review')->assertHasErrors('monthlyAmount')->assertSet('step', 'summary');
    expect(ContactMessage::query()->count())->toBe(0);
})->with(['0', '-5', '1e3', '12.345', '10000.01', 'not-money']);

it('does not save bot requests or requests above the rate limit', function () {
    Mail::fake();
    $child = checkoutProfile();
    $form = filledSponsorshipCheckout($child)->set('website', 'spam.example')->call('review');
    $this->travel(5)->seconds();
    $form->set('consent', true)->call('submit')->assertSet('step', 'complete');
    expect(ContactMessage::query()->count())->toBe(0);
    Mail::assertNothingQueued();

    for ($i = 0; $i < 3; $i++) {
        RateLimiter::hit('public-form:sponsorship-checkout:127.0.0.1', 60);
    }
    filledSponsorshipCheckout($child)->call('review')->set('consent', true)
        ->call('submit')->assertHasErrors('rate')->assertSet('step', 'review');
});

it('rechecks profile publication when opening and submitting checkout', function () {
    $child = checkoutProfile();
    $form = filledSponsorshipCheckout($child)->call('review');
    $child->update(['guardian_consent_at' => null]);
    $form->set('consent', true)->call('submit')->assertNotFound();
    $this->get(route('sponsor-a-child.checkout', $child->public_sponsorship_slug))->assertNotFound();
    expect(ContactMessage::query()->count())->toBe(0);
});

it('locks checkout identity and reviewed state against browser tampering', function (string $property, mixed $value) {
    Livewire::test(SponsorshipCheckout::class, ['profile' => checkoutProfile()->public_sponsorship_id])
        ->set($property, $value);
})->with([
    ['profile', 'another-child'], ['step', 'complete'], ['requestId', 'another-request'], ['reviewedDetails', ['monthly_cents' => 0]],
])->throws(CannotUpdateLockedPropertyException::class);

it('lets authorised staff save separate public story sections without exposing case notes', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $child = checkoutProfile(['notes' => 'PRIVATE INTERNAL NOTES']);

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->set('publicCommunityStory', 'Public community context.')->set('publicCountryStory', 'Public country context.')
        ->call('saveSponsorship')->assertHasNoErrors();

    expect($child->fresh()->public_community_story)->toBe('Public community context.')
        ->and($child->fresh()->public_country_story)->toBe('Public country context.');
    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))
        ->assertOk()->assertSee('Public community context.')->assertSee('Public country context.')
        ->assertDontSee('PRIVATE INTERNAL NOTES');
});

it('saves a pending sponsorship alongside the staff message', function () {
    Mail::fake();
    $child = checkoutProfile();

    $component = filledSponsorshipCheckout($child)
        ->set('monthlyAmount', '75')
        ->call('review')
        ->set('consent', true);
    $this->travel(5)->seconds();
    $component->call('submit')->assertHasNoErrors()->assertSet('step', 'complete');

    $sponsorship = Sponsorship::query()->sole();

    expect($sponsorship)
        ->beneficiary_id->toBe($child->id)
        ->sponsor_email->toBe('akua@example.test')
        ->monthly_amount_cents->toBe(7500)
        ->status->toBe(SponsorshipStatus::Pending)
        ->contact_message_id->toBe(ContactMessage::query()->sole()->id);
    expect($child->isSponsored())->toBeFalse();
});

it('shows a sponsored child as sponsored without revealing the sponsor', function () {
    $child = checkoutProfile();
    Sponsorship::factory()->active()->create(['beneficiary_id' => $child->id, 'sponsor_name' => 'Secret Sponsor']);

    $this->get(route('sponsor-a-child'))->assertOk()->assertSee('Sponsored')->assertDontSee('Secret Sponsor');

    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))
        ->assertOk()
        ->assertSee('already has a sponsor')
        ->assertDontSee('Secret Sponsor')
        ->assertDontSee(route('sponsor-a-child.checkout', $child->public_sponsorship_slug));

    $this->get(route('sponsor-a-child.checkout', $child->public_sponsorship_slug))
        ->assertRedirect(route('sponsor-a-child.show', $child->public_sponsorship_slug));
});

it('stops a request if the child was sponsored in the meantime', function () {
    Mail::fake();
    $child = checkoutProfile();
    $component = filledSponsorshipCheckout($child)->call('review')->set('consent', true);
    Sponsorship::factory()->active()->create(['beneficiary_id' => $child->id]);

    $component->call('submit')->assertHasErrors('review');

    expect(Sponsorship::query()->where('status', SponsorshipStatus::Pending->value)->count())->toBe(0);
});
