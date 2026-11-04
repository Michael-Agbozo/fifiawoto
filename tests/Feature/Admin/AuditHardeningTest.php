<?php

use App\Enums\VolunteerApplicationStatus;
use App\Mail\ContactMessageReply;
use App\Mail\VolunteerApplicationDecision;
use App\Models\ContactMessage;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('restricts the dashboard activity export to users who can export reports', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());
    $this->get(route('admin.dashboard.export'))->assertOk();

    $this->actingAs(User::factory()->mediaManager()->create());
    $this->get(route('admin.dashboard.export'))->assertForbidden();
    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.dashboard.export'), false);
});

it('guards the system logs component itself, not just its route', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.system-logs')->assertForbidden();
});

it('lets the owner download the full log', function () {
    $this->actingAs(User::factory()->owner()->create());
    if (! file_exists(storage_path('logs/laravel.log'))) {
        file_put_contents(storage_path('logs/laravel.log'), '');
    }

    Livewire::test('admin.system-logs')->call('downloadLog')->assertFileDownloaded();
});

it('does not send the same inbox reply twice on a double submit', function () {
    Mail::fake();
    $this->actingAs($admin = User::factory()->superAdmin()->create());
    $message = ContactMessage::factory()->create();
    RateLimiter::clear('admin-mail:inbox-reply:'.$admin->id.':'.$message->id);

    $reply = fn () => Livewire::test('admin.inbox')
        ->call('openMessage', $message->id)
        ->call('startReply')
        ->set('replySubject', 'Re: hello')
        ->set('replyBody', 'Thanks for writing to us.')
        ->call('sendReply');

    $reply()->assertHasNoErrors();
    $reply()->assertHasErrors('reply');

    Mail::assertQueuedCount(1);
    Mail::assertQueued(ContactMessageReply::class);
});

it('does not email a rejected volunteer twice', function () {
    Mail::fake();
    $this->actingAs(User::factory()->superAdmin()->create());
    $application = VolunteerApplication::factory()->create(['status' => VolunteerApplicationStatus::New->value]);

    Livewire::test('admin.volunteers')->call('reject', $application->id)->call('reject', $application->id);

    Mail::assertQueued(VolunteerApplicationDecision::class, 1);
});

it('prefills the contact subject from the query string', function () {
    $this->get(route('contact', ['subject' => 'donation']))->assertOk();

    Livewire::withQueryParams(['subject' => 'donation'])->test('site.contact-form')->assertSet('subject', 'donation');
    Livewire::withQueryParams(['subject' => '<script>'])->test('site.contact-form')->assertSet('subject', '');
});

it('resolves media paths for bundled assets, uploads and remote URLs', function () {
    expect(MediaUrl::for(null))->toBeNull()
        ->and(MediaUrl::for('https://cdn.example/a.jpg'))->toBe('https://cdn.example/a.jpg')
        ->and(MediaUrl::for('images/foundation/outreach-1.jpg'))->toBe(asset('images/foundation/outreach-1.jpg'))
        ->and(MediaUrl::for('leaders/me.jpg'))->toBe(Storage::disk('public')->url('leaders/me.jpg'))
        ->and(MediaUrl::for('/storage/events/hero.jpg'))->toBe(Storage::disk('public')->url('events/hero.jpg'));
});

it('offers a copy button for 2FA recovery codes', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.two-factor.recovery-codes')
        ->assertSee('data-test="copy-recovery-codes"', false)
        ->assertSee('Copy all');
});
