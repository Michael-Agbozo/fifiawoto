<?php

use App\Enums\NewsletterCampaignStatus;
use App\Jobs\SendNewsletterCampaignEmail;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('saves a draft', function () {
    Livewire::test('admin.newsletter-campaigns')
        ->call('startCreate')
        ->set('subject', 'November update')
        ->set('body', 'A short note to say thank you.')
        ->call('save')
        ->assertHasNoErrors();

    expect(NewsletterCampaign::query()->sole())
        ->subject->toBe('November update')
        ->status->toBe(NewsletterCampaignStatus::Draft);

    $this->get(route('admin.newsletter.campaigns'))->assertOk()->assertSee('November update');
});

it('sends only to active subscribers, once each', function () {
    $active = NewsletterSubscriber::factory()->create();
    $gone = NewsletterSubscriber::factory()->create(['unsubscribed_at' => now()]);
    $campaign = NewsletterCampaign::factory()->create();

    Livewire::test('admin.newsletter-campaigns')
        ->call('askSend', $campaign->id)
        ->call('send');

    Mail::assertSent(NewsletterCampaignMail::class, 1);
    Mail::assertSent(NewsletterCampaignMail::class, fn ($mail) => $mail->hasTo($active->email));
    Mail::assertNotSent(NewsletterCampaignMail::class, fn ($mail) => $mail->hasTo($gone->email));
    expect($campaign->refresh()->status)->toBe(NewsletterCampaignStatus::Sent);

    Livewire::test('admin.newsletter-campaigns')->set('confirmSendId', $campaign->id)->call('send');
    Mail::assertSent(NewsletterCampaignMail::class, 1);
});

it('skips someone who unsubscribes after the send starts', function () {
    $subscriber = NewsletterSubscriber::factory()->create(['unsubscribed_at' => now()]);
    $campaign = NewsletterCampaign::factory()->create(['status' => NewsletterCampaignStatus::Sending]);
    $recipient = $campaign->recipients()->create(['newsletter_subscriber_id' => $subscriber->id, 'email' => $subscriber->email]);

    (new SendNewsletterCampaignEmail($recipient->id))->handle();

    Mail::assertNothingSent();
    expect($recipient->refresh()->status)->toBe(NewsletterCampaignRecipient::STATUS_SKIPPED)
        ->and($campaign->refresh()->status)->toBe(NewsletterCampaignStatus::Sent);
});

it('marks a failed email and can retry it', function () {
    $subscriber = NewsletterSubscriber::factory()->create();
    $campaign = NewsletterCampaign::factory()->create(['status' => NewsletterCampaignStatus::Sending]);
    $recipient = $campaign->recipients()->create(['newsletter_subscriber_id' => $subscriber->id, 'email' => $subscriber->email]);

    (new SendNewsletterCampaignEmail($recipient->id))->failed(new RuntimeException('SMTP down'));
    expect($recipient->refresh()->status)->toBe(NewsletterCampaignRecipient::STATUS_FAILED);

    Livewire::test('admin.newsletter-campaigns')->call('retryFailed', $campaign->id);

    expect($recipient->refresh()->status)->toBe(NewsletterCampaignRecipient::STATUS_SENT);
    Mail::assertSent(NewsletterCampaignMail::class, 1);
});

it('sends a test to the signed-in user only and keeps the draft', function () {
    NewsletterSubscriber::factory()->create();
    $campaign = NewsletterCampaign::factory()->create();

    Livewire::test('admin.newsletter-campaigns')->call('sendTest', $campaign->id);

    Mail::assertSent(NewsletterCampaignMail::class, 1);
    Mail::assertSent(NewsletterCampaignMail::class, fn ($mail) => $mail->hasTo(auth()->user()->email));
    expect($campaign->refresh()->status)->toBe(NewsletterCampaignStatus::Draft);
});

it('renders Markdown but strips raw HTML and includes a working unsubscribe link', function () {
    $subscriber = NewsletterSubscriber::factory()->create();
    $campaign = NewsletterCampaign::factory()->create(['body' => "**Thanks** <script>alert(1)</script>\n\n[bad](javascript:alert(1))"]);

    $html = (new NewsletterCampaignMail($campaign, $subscriber))->render();

    expect($html)->toContain('Thanks</strong>')
        ->not->toContain('<script>')
        ->not->toContain('javascript:')
        ->toContain('/newsletter/unsubscribe/'.$subscriber->id);
});

it('supports one-click unsubscribe without a CSRF token', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    $this->post(URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber]))->assertOk();

    expect($subscriber->refresh()->unsubscribed_at)->not->toBeNull();
});

it('does not allow changing a sent newsletter', function () {
    $campaign = NewsletterCampaign::factory()->create(['status' => NewsletterCampaignStatus::Sent]);

    Livewire::test('admin.newsletter-campaigns')->call('startEdit', $campaign->id)->assertNotFound();
});

it('stops a role without the send permission from sending', function () {
    $user = User::factory()->create(['role' => 'volunteer_coordinator', 'permissions' => ['newsletter.view', 'newsletter.create']]);
    $campaign = NewsletterCampaign::factory()->create();

    Livewire::actingAs($user)->test('admin.newsletter-campaigns')->call('askSend', $campaign->id)->assertForbidden();
});

it('restarts a campaign whose job stopped without emailing anyone twice', function () {
    Mail::fake();
    $campaign = NewsletterCampaign::factory()->create(['status' => NewsletterCampaignStatus::Sending, 'started_at' => now()->subHours(2)]);
    $done = NewsletterSubscriber::factory()->create();
    NewsletterSubscriber::factory()->create();
    $campaign->recipients()->create([
        'newsletter_subscriber_id' => $done->id,
        'email' => $done->email,
        'status' => NewsletterCampaignRecipient::STATUS_SENT,
    ]);

    Livewire::test('admin.newsletter-campaigns')->call('resume', $campaign->id);

    Mail::assertSentCount(1);
    expect($campaign->recipients()->where('status', NewsletterCampaignRecipient::STATUS_SENT)->count())->toBe(2)
        ->and($campaign->fresh()->status)->toBe(NewsletterCampaignStatus::Sent);
});

it('does not restart a campaign that has only just started', function () {
    Mail::fake();
    NewsletterSubscriber::factory()->create();
    $campaign = NewsletterCampaign::factory()->create(['status' => NewsletterCampaignStatus::Sending, 'started_at' => now()->subMinutes(5)]);

    Livewire::test('admin.newsletter-campaigns')->call('resume', $campaign->id);

    Mail::assertNothingSent();
    expect($campaign->recipients()->count())->toBe(0);
});
