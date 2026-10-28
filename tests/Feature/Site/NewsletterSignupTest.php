<?php

use App\Mail\NewsletterResubscribeConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

it('persists a new subscriber via the Livewire form', function () {
    Livewire::test('site.newsletter-signup', ['source' => 'home'])
        ->set('name', 'Ama Test')
        ->set('email', 'ama@example.test')
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee('Thank you for subscribing.');

    expect(NewsletterSubscriber::query()->where('email', 'ama@example.test')->exists())->toBeTrue();
});

it('rejects an invalid email', function () {
    Livewire::test('site.newsletter-signup')
        ->set('email', 'not-an-email')
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('subscribe')
        ->assertHasErrors(['email' => 'email']);

    expect(NewsletterSubscriber::query()->count())->toBe(0);
});

it('emails a confirmation link instead of silently re-subscribing an unsubscribed address', function () {
    Mail::fake();

    $subscriber = NewsletterSubscriber::factory()->create([
        'email' => 'existing@example.test',
        'unsubscribed_at' => now()->subDay(),
        'source' => 'footer',
    ]);

    Livewire::test('site.newsletter-signup', ['source' => 'home'])
        ->set('email', 'existing@example.test')
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $subscriber->refresh();
    expect($subscriber->unsubscribed_at)->not->toBeNull()
        ->and($subscriber->source)->toBe('footer');

    $confirmUrl = null;
    Mail::assertQueued(NewsletterResubscribeConfirmation::class, function (NewsletterResubscribeConfirmation $mail) use ($subscriber, &$confirmUrl) {
        $confirmUrl = $mail->content()->with['confirmUrl'];

        return $mail->hasTo('existing@example.test') && $mail->subscriber->is($subscriber);
    });

    $this->get($confirmUrl)->assertOk()->assertSee('Confirm your subscription');
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();

    $this->post($confirmUrl)->assertOk()->assertSee('Subscription confirmed');
    expect($subscriber->fresh()->unsubscribed_at)->toBeNull();
});

it('rejects tampered or expired re-subscribe links', function () {
    $subscriber = NewsletterSubscriber::factory()->create(['unsubscribed_at' => now()->subDay()]);

    $this->post(route('newsletter.resubscribe.confirm', $subscriber))->assertForbidden();

    $url = URL::temporarySignedRoute('newsletter.resubscribe', now()->addHour(), ['subscriber' => $subscriber]);
    $this->travel(2)->hours();
    $this->post($url)->assertForbidden();

    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('does not overwrite an existing subscriber\'s details', function () {
    Mail::fake();

    NewsletterSubscriber::factory()->create([
        'name' => 'Original Name',
        'email' => 'active@example.test',
        'source' => 'footer',
    ]);

    Livewire::test('site.newsletter-signup', ['source' => 'popup'])
        ->set('name', 'Attacker')
        ->set('email', '  Active@Example.TEST ')
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $record = NewsletterSubscriber::query()->sole();
    expect($record->name)->toBe('Original Name')
        ->and($record->source)->toBe('footer');

    Mail::assertNothingQueued();
});

it('stores new emails lowercased and trimmed', function () {
    Livewire::test('site.newsletter-signup')
        ->set('email', '  New.Person@Example.TEST ')
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('subscribe')
        ->assertHasNoErrors();

    expect(NewsletterSubscriber::query()->sole()->email)->toBe('new.person@example.test');
});

it('locks the source and compact props', function () {
    Livewire::test('site.newsletter-signup', ['source' => 'footer'])
        ->set('source', 'hacked');
})->throws(CannotUpdateLockedPropertyException::class);

it('unsubscribes via a signed link after confirmation', function () {
    $subscriber = NewsletterSubscriber::factory()->create();
    $url = URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber]);

    $this->get(route('newsletter.unsubscribe', $subscriber))->assertForbidden();

    $this->get($url)->assertOk()->assertSee('Unsubscribe from our newsletter');
    expect($subscriber->fresh()->unsubscribed_at)->toBeNull();

    $this->post($url)->assertOk()->assertSee('You have been unsubscribed');
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('records the source label per signup location', function () {
    Livewire::test('site.newsletter-signup', ['source' => 'footer'])
        ->set('email', 'footerfan@example.test')
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('subscribe')
        ->assertHasNoErrors();

    expect(NewsletterSubscriber::query()->where('email', 'footerfan@example.test')->value('source'))
        ->toBe('footer');
});
