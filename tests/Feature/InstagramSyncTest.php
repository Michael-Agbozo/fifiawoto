<?php

use App\Enums\InstagramSource;
use App\Models\InstagramPost;
use Illuminate\Support\Facades\Http;

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
