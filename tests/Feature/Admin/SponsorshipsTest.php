<?php

use App\Enums\SponsorshipStatus;
use App\Mail\DonationReceipt;
use App\Mail\SponsorshipActivated;
use App\Mail\SponsorshipReminder;
use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Sponsorship;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('shows sponsors on the admin page', function () {
    Sponsorship::factory()->create(['sponsor_name' => 'Ama Mensah']);

    $this->get(route('admin.sponsorships.index'))->assertOk()->assertSee('Ama Mensah');
});

it('blocks roles without sponsorship access', function () {
    $this->actingAs(User::factory()->create(['role' => 'media_manager']))
        ->get(route('admin.sponsorships.index'))
        ->assertForbidden();
});

it('approves a request and emails the sponsor', function () {
    $sponsorship = Sponsorship::factory()->create();

    Livewire::test('admin.sponsorships')
        ->call('startActivate', $sponsorship->id)
        ->set('activate_on', '2026-11-10')
        ->call('activate')
        ->assertHasNoErrors();

    $sponsorship->refresh();
    expect($sponsorship->status)->toBe(SponsorshipStatus::Active)
        ->and($sponsorship->next_due_on->toDateString())->toBe('2026-11-10');
    Mail::assertQueued(SponsorshipActivated::class);
});

it('refuses a second active sponsor for one child', function () {
    $child = Beneficiary::factory()->create();
    Sponsorship::factory()->active()->create(['beneficiary_id' => $child->id]);
    $second = Sponsorship::factory()->create(['beneficiary_id' => $child->id]);

    Livewire::test('admin.sponsorships')
        ->call('startActivate', $second->id)
        ->call('activate')
        ->assertHasErrors('activate_on');

    expect($second->refresh()->status)->toBe(SponsorshipStatus::Pending);
});

it('moves the due date when a payment is recorded and back when it is deleted', function () {
    $sponsorship = Sponsorship::factory()->active('2026-01-31')->create();

    Livewire::test('admin.sponsorships')
        ->call('startPayment', $sponsorship->id)
        ->set('payment_months', 3)
        ->set('payment_received_at', '2026-02-01')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $donation = Donation::query()->sole();
    expect($donation->sponsorship_id)->toBe($sponsorship->id)
        ->and($donation->amount_cents)->toBe(5000)
        ->and($sponsorship->refresh()->next_due_on->toDateString())->toBe('2026-04-30');
    Mail::assertQueued(DonationReceipt::class);

    $donation->delete();
    expect($sponsorship->refresh()->next_due_on->toDateString())->toBe('2026-01-31');
});

it('does not move the due date for a one-time gift', function () {
    $sponsorship = Sponsorship::factory()->active('2026-01-15')->create();

    Livewire::test('admin.sponsorships')
        ->call('startPayment', $sponsorship->id)
        ->set('payment_months', 0)
        ->call('recordPayment')
        ->assertHasNoErrors();

    expect($sponsorship->refresh()->next_due_on->toDateString())->toBe('2026-01-15');
});

it('adds an arrangement agreed outside the website as active', function () {
    $child = Beneficiary::factory()->create();

    Livewire::test('admin.sponsorships')
        ->call('startCreate')
        ->set('beneficiary_id', $child->id)
        ->set('sponsor_name', 'Kofi Boateng')
        ->set('sponsor_email', 'kofi@example.org')
        ->set('monthly_amount', '40')
        ->call('save')
        ->assertHasNoErrors();

    expect(Sponsorship::query()->sole())
        ->status->toBe(SponsorshipStatus::Active)
        ->monthly_amount_cents->toBe(4000);
});

it('sends each reminder once per due date', function () {
    $upcoming = Sponsorship::factory()->active(today()->addDays(2)->toDateString())->create();
    $overdue = Sponsorship::factory()->active(today()->subDays(8)->toDateString())->create();
    Sponsorship::factory()->active(today()->addDays(20)->toDateString())->create();
    Sponsorship::factory()->active(today()->subDays(2)->toDateString())->create();
    Sponsorship::factory()->active(today()->addDay()->toDateString())->create(['reminders_enabled' => false]);

    $this->artisan('sponsorships:send-reminders')->assertSuccessful();
    $this->artisan('sponsorships:send-reminders')->assertSuccessful();

    Mail::assertQueued(SponsorshipReminder::class, 2);
    Mail::assertQueued(SponsorshipReminder::class, fn ($mail) => $mail->hasTo($upcoming->sponsor_email));
    Mail::assertQueued(SponsorshipReminder::class, fn ($mail) => $mail->hasTo($overdue->sponsor_email));
});

it('names the child only by their public name in reminders', function () {
    $child = Beneficiary::factory()->create(['full_name' => 'Private Fullname', 'public_display_name' => 'Esi']);
    $sponsorship = Sponsorship::factory()->active()->create(['beneficiary_id' => $child->id]);

    $html = (new SponsorshipReminder($sponsorship))->render();

    expect($html)->toContain('Esi')->not->toContain('Private Fullname');
});

it('lets a sponsor stop reminders from the signed link', function () {
    $sponsorship = Sponsorship::factory()->active()->create();
    $url = URL::signedRoute('sponsorship-reminders.stop', ['sponsorship' => $sponsorship]);

    $this->get($url)->assertOk();
    expect($sponsorship->refresh()->reminders_enabled)->toBeTrue();

    $this->post($url)->assertOk();
    expect($sponsorship->refresh()->reminders_enabled)->toBeFalse();

    $this->get(route('sponsorship-reminders.stop', ['sponsorship' => $sponsorship]))->assertForbidden();
});
