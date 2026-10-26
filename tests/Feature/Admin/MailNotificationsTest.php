<?php

use App\Enums\ContactSubject;
use App\Enums\UserRole;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Enums\VolunteerRole;
use App\Mail\BeneficiaryApplicationConverted;
use App\Mail\ContactFormReceived;
use App\Mail\ContactMessageReply;
use App\Mail\DonationReceipt;
use App\Mail\TeamInvitation;
use App\Mail\VolunteerApplicationDecision;
use App\Mail\VolunteerApplicationReceived;
use App\Models\BeneficiaryApplication;
use App\Models\ContactMessage;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

it('emails the admin when a volunteer applies publicly', function () {
    Livewire::test('site.volunteer-application-form')
        ->set('full_name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('phone', '+233 200 000 000')
        ->set('country', 'Ghana')
        ->set('interests', [VolunteerInterest::cases()[0]->value])
        ->set('availability', VolunteerAvailability::cases()[0]->value)
        ->set('motivation', 'I really want to help our community thrive together.')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors();

    Mail::assertQueued(VolunteerApplicationReceived::class, function ($mail) {
        return $mail->hasTo(config('notifications.admin_email'));
    });
});

it('emails the applicant when a volunteer application is approved', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $app = VolunteerApplication::factory()->create([
        'status' => VolunteerApplicationStatus::New->value,
        'email' => 'volunteer@example.com',
    ]);

    Livewire::test('admin.volunteers')
        ->set('applicationRoleChoice.'.$app->id, VolunteerRole::Event->value)
        ->call('approve', $app->id);

    Mail::assertQueued(VolunteerApplicationDecision::class, function ($mail) use ($app) {
        return $mail->hasTo($app->email) && $mail->approved === true;
    });
});

it('emails the applicant when a volunteer application is rejected', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $app = VolunteerApplication::factory()->create([
        'status' => VolunteerApplicationStatus::New->value,
        'email' => 'rejected@example.com',
    ]);

    Livewire::test('admin.volunteers')->call('reject', $app->id);

    Mail::assertQueued(VolunteerApplicationDecision::class, function ($mail) use ($app) {
        return $mail->hasTo($app->email) && $mail->approved === false;
    });
});

it('emails the applicant when a beneficiary application is converted', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $app = BeneficiaryApplication::factory()->create([
        'email' => 'beneficiary@example.com',
    ]);

    Livewire::test('admin.beneficiary-applications')->call('convertToBeneficiary', $app->id);

    Mail::assertQueued(BeneficiaryApplicationConverted::class, function ($mail) use ($app) {
        return $mail->hasTo($app->email);
    });
});

it('emails a receipt when a donation is recorded with a donor email', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.donations')
        ->call('startCreate')
        ->set('donor_name', 'Generous Donor')
        ->set('donor_email', 'donor@example.com')
        ->set('amount', '50.00')
        ->set('currency', 'USD')
        ->set('payment_method', 'cash')
        ->set('received_at', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertQueued(DonationReceipt::class, function ($mail) {
        return $mail->hasTo('donor@example.com');
    });
});

it('does not email a receipt when no donor email is provided', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.donations')
        ->call('startCreate')
        ->set('donor_name', 'Anonymous')
        ->set('amount', '20.00')
        ->set('currency', 'USD')
        ->set('payment_method', 'cash')
        ->set('received_at', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertNotQueued(DonationReceipt::class);
});

it('emails an invitation when a teammate is invited', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.users')
        ->call('startInvite')
        ->set('name', 'New Teammate')
        ->set('email', 'teammate@example.com')
        ->set('role', UserRole::FoundationStaff->value)
        ->call('invite')
        ->assertHasNoErrors();

    Mail::assertQueued(TeamInvitation::class, function ($mail) {
        return $mail->hasTo('teammate@example.com');
    });
});

it('emails the admin when the contact form is submitted', function () {
    Livewire::test('site.contact-form')
        ->set('full_name', 'Curious Visitor')
        ->set('email', 'visitor@example.com')
        ->set('subject', ContactSubject::cases()[0]->value)
        ->set('message', 'I would love to learn more about what you do.')
        ->set('consent', true)
        ->call('submit')
        ->assertHasNoErrors();

    Mail::assertQueued(ContactFormReceived::class, function ($mail) {
        return $mail->hasTo(config('notifications.admin_email'));
    });
});

it('emails the sender when an admin replies from the inbox', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $message = ContactMessage::factory()->create(['email' => 'visitor@example.com']);

    Livewire::test('admin.inbox')
        ->call('openMessage', $message->id)
        ->call('startReply')
        ->set('replySubject', 'Re: your enquiry')
        ->set('replyBody', 'Thanks for reaching out — here is more detail.')
        ->call('sendReply')
        ->assertHasNoErrors()
        ->assertSet('flashMessage', 'Reply sent to visitor@example.com.');

    Mail::assertQueued(ContactMessageReply::class, fn ($mail) => $mail->hasTo('visitor@example.com'));
});

it('encrypts the queued team invitation because it carries a temporary password', function () {
    expect(new TeamInvitation(User::factory()->make(), 'temp-pass'))
        ->toBeInstanceOf(ShouldBeEncrypted::class);
});
