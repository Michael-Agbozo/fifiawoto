<?php

use App\Enums\InstagramSource;
use App\Models\InstagramPost;
use App\Models\Setting;
use App\Support\InstagramToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('is a no-op until an access token is configured', function () {
    config(['services.instagram.access_token' => null]);
    Http::fake();

    $this->artisan('instagram:sync')
        ->expectsOutputToContain('not configured')
        ->assertSuccessful();

    Http::assertNothingSent();
});

it('imports new posts as unapproved and keeps existing moderation flags', function () {
    config(['services.instagram.access_token' => 'token-123']);

    InstagramPost::factory()->create([
        'external_id' => 'existing',
        'permalink' => 'https://instagram.com/p/old',
        'is_approved' => true,
        'is_hidden' => true,
    ]);

    Http::fake([
        'graph.instagram.com/*' => Http::response(['data' => [
            [
                'id' => 'new-1',
                'caption' => 'Back to school drive',
                'media_type' => 'IMAGE',
                'media_url' => 'https://cdn.example/'.str_repeat('a', 400).'.jpg',
                'permalink' => 'https://instagram.com/p/new-1',
                'timestamp' => '2026-10-01T10:00:00+0000',
            ],
            [
                'id' => 'existing',
                'caption' => 'Updated caption',
                'media_type' => 'IMAGE',
                'permalink' => 'https://instagram.com/p/old',
                'timestamp' => '2026-09-01T10:00:00+0000',
            ],
        ]]),
    ]);

    $this->artisan('instagram:sync')->assertSuccessful();

    $new = InstagramPost::query()->where('external_id', 'new-1')->sole();
    expect($new->is_approved)->toBeFalse()
        ->and($new->source)->toBe(InstagramSource::Api)
        ->and($new->posted_at->toDateString())->toBe('2026-10-01');

    $existing = InstagramPost::query()->where('external_id', 'existing')->sole();
    expect($existing->caption)->toBe('Updated caption')
        ->and($existing->is_approved)->toBeTrue()
        ->and($existing->is_hidden)->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'access_token=token-123'));
});

it('fails cleanly when the API returns an error', function () {
    config(['services.instagram.access_token' => 'expired']);
    Http::fake(['graph.instagram.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 400)]);

    $this->artisan('instagram:sync')
        ->expectsOutputToContain('Invalid OAuth access token')
        ->assertFailed();

    expect(InstagramPost::query()->count())->toBe(0);
});

it('is scheduled hourly', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('instagram:sync')
        ->assertSuccessful();
});

it('never prints or logs the access token when Instagram is unreachable', function () {
    config(['services.instagram.access_token' => 'secret-token-xyz']);
    Http::fake(fn () => throw new ConnectionException('cURL error 28 for https://graph.instagram.com/me/media?access_token=secret-token-xyz'));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn (string $message, array $context) => ! str_contains($message.json_encode($context), 'secret-token-xyz')
            && str_contains($context['error'], '[redacted]'));

    $this->artisan('instagram:sync')
        ->expectsOutputToContain('Could not reach Instagram')
        ->doesntExpectOutputToContain('secret-token-xyz')
        ->assertFailed();
});

it('reports an expired token clearly', function () {
    config(['services.instagram.access_token' => 'expired']);
    Http::fake(['graph.instagram.com/*' => Http::response(['error' => ['message' => 'Session has expired', 'code' => 190]], 400)]);

    Log::shouldReceive('error')->once()->withArgs(fn (string $message) => str_contains($message, 'invalid or expired'));

    $this->artisan('instagram:sync')
        ->expectsOutputToContain('invalid or expired')
        ->assertFailed();
});

it('refreshes the long-lived token and uses the persisted token for syncing', function () {
    config(['services.instagram.access_token' => 'env-token']);

    Http::fake([
        'graph.instagram.com/refresh_access_token*' => Http::response([
            'access_token' => 'refreshed-token',
            'token_type' => 'bearer',
            'expires_in' => 5_184_000,
        ]),
        'graph.instagram.com/*' => Http::response(['data' => []]),
    ]);

    $this->artisan('instagram:refresh-token')
        ->expectsOutputToContain('refreshed')
        ->doesntExpectOutputToContain('refreshed-token')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'grant_type=ig_refresh_token')
        && str_contains($request->url(), 'access_token=env-token'));

    expect(InstagramToken::current())->toBe('refreshed-token')
        ->and(Setting::read(InstagramToken::SETTING_KEY))->not->toContain('refreshed-token');

    $this->artisan('instagram:sync')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/media') && str_contains($request->url(), 'access_token=refreshed-token'));
});

it('keeps the current token when refreshing fails', function () {
    config(['services.instagram.access_token' => 'env-token']);
    Http::fake(['graph.instagram.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token', 'code' => 190]], 400)]);

    $this->artisan('instagram:refresh-token')
        ->expectsOutputToContain('refresh failed')
        ->assertFailed();

    expect(InstagramToken::current())->toBe('env-token');
});

it('schedules the token refresh weekly', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('instagram:refresh-token')
        ->assertSuccessful();
});

it('adopts a manually added post instead of duplicating it, keeping its flags', function () {
    config(['services.instagram.access_token' => 'token-123']);

    $manual = InstagramPost::factory()->create([
        'external_id' => sha1('https://instagram.com/p/shared/'),
        'permalink' => 'https://instagram.com/p/shared/',
        'source' => InstagramSource::Manual,
        'is_approved' => false,
        'is_hidden' => true,
    ]);

    Http::fake([
        'graph.instagram.com/*' => Http::response(['data' => [[
            'id' => 'graph-shared',
            'media_type' => 'IMAGE',
            'permalink' => 'https://www.instagram.com/p/shared/',
        ]]]),
    ]);

    $this->artisan('instagram:sync')->assertSuccessful();
    $this->artisan('instagram:sync')->assertSuccessful();

    $manual->refresh();
    expect(InstagramPost::query()->count())->toBe(1)
        ->and($manual->external_id)->toBe('graph-shared')
        ->and($manual->is_approved)->toBeFalse()
        ->and($manual->is_hidden)->toBeTrue();
});
