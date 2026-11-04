<?php

use App\Enums\UserRole;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Livewire\Livewire;

function newsletterCsv($component): string
{
    return (string) base64_decode(data_get($component->effects, 'download.content'));
}

it('forbids the newsletter page without newsletter.view', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Volunteer, 'permissions' => []]));

    Livewire::test('admin.newsletter')->assertForbidden();
});

it('forbids unsubscribe and resubscribe without newsletter.update', function () {
    $active = NewsletterSubscriber::factory()->create(['unsubscribed_at' => null]);
    $inactive = NewsletterSubscriber::factory()->create(['unsubscribed_at' => now()]);
    $this->actingAs(User::factory()->create(['role' => UserRole::Volunteer, 'permissions' => ['newsletter.view']]));

    Livewire::test('admin.newsletter')->call('unsubscribe', $active->id)->assertForbidden();
    Livewire::test('admin.newsletter')->call('resubscribe', $inactive->id)->assertForbidden();

    expect($active->refresh()->unsubscribed_at)->toBeNull()
        ->and($inactive->refresh()->unsubscribed_at)->not->toBeNull();
});

it('lets a newsletter manager unsubscribe a subscriber', function () {
    $sub = NewsletterSubscriber::factory()->create(['unsubscribed_at' => null]);
    $this->actingAs(User::factory()->mediaManager()->create());

    Livewire::test('admin.newsletter')->call('unsubscribe', $sub->id)->assertOk();

    expect($sub->refresh()->unsubscribed_at)->not->toBeNull();
});

it('lets a newsletter viewer export, with formulas escaped', function () {
    NewsletterSubscriber::factory()->create([
        'email' => 'fan@example.test',
        'name' => '=HYPERLINK("http://evil.test","x")',
        'unsubscribed_at' => null,
    ]);
    $this->actingAs(User::factory()->create(['role' => UserRole::Volunteer, 'permissions' => ['newsletter.view']]));

    $csv = newsletterCsv(Livewire::test('admin.newsletter')->call('exportCsv'));

    expect($csv)->toContain('fan@example.test')
        ->toContain('"\'=HYPERLINK(')
        ->not->toContain(',"=HYPERLINK(');
});
