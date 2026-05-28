<?php

use App\Models\NewsletterSubscriber;
use Livewire\Livewire;

it('persists a new subscriber via the Livewire form', function () {
    Livewire::test('site.newsletter-signup', ['source' => 'home'])
        ->set('name', 'Ama Test')
        ->set('email', 'ama@example.test')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee('Thank you for subscribing.');

    expect(NewsletterSubscriber::query()->where('email', 'ama@example.test')->exists())->toBeTrue();
});

it('rejects an invalid email', function () {
    Livewire::test('site.newsletter-signup')
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors(['email' => 'email']);

    expect(NewsletterSubscriber::query()->count())->toBe(0);
});

it('treats duplicate emails as an update, not an error', function () {
    NewsletterSubscriber::factory()->create([
        'email' => 'existing@example.test',
        'unsubscribed_at' => now()->subDay(),
        'source' => 'footer',
    ]);

    Livewire::test('site.newsletter-signup', ['source' => 'home'])
        ->set('email', 'existing@example.test')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $record = NewsletterSubscriber::query()->where('email', 'existing@example.test')->sole();

    expect($record->unsubscribed_at)->toBeNull()
        ->and($record->source)->toBe('home');
});

it('records the source label per signup location', function () {
    Livewire::test('site.newsletter-signup', ['source' => 'footer'])
        ->set('email', 'footerfan@example.test')
        ->call('subscribe')
        ->assertHasNoErrors();

    expect(NewsletterSubscriber::query()->where('email', 'footerfan@example.test')->value('source'))
        ->toBe('footer');
});
