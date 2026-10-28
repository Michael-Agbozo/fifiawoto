<?php

use App\Enums\UserRole;
use App\Http\Middleware\EnsureUserHasRole;
use App\Mail\ContactFormReceived;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\HandleRequests;

/**
 * Pull the Livewire snapshot for the given component out of a rendered page.
 */
function livewireSnapshotFrom(string $html, string $componentName): string
{
    preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);

    foreach ($matches[1] as $encoded) {
        $snapshot = html_entity_decode($encoded, ENT_QUOTES);

        if ((json_decode($snapshot, true)['memo']['name'] ?? null) === $componentName) {
            return $snapshot;
        }
    }

    throw new RuntimeException("No snapshot for [{$componentName}] found on the page.");
}

it('registers the role middleware as Livewire persistent middleware', function () {
    expect(Livewire::getPersistentMiddleware())->toContain(EnsureUserHasRole::class);
});

it('re-applies the role route middleware on Livewire update requests', function () {
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin);

    $snapshot = livewireSnapshotFrom($this->get(route('admin.users.index'))->assertOk()->getContent(), 'admin.users');
    $updateUri = app(HandleRequests::class)->getUpdateUri();

    $payload = ['components' => [[
        'snapshot' => $snapshot,
        'updates' => [],
        'calls' => [['path' => '', 'method' => 'cancelInvite', 'params' => []]],
    ]]];

    $this->withHeaders(['X-Livewire' => '1'])->postJson($updateUri, $payload)->assertOk();

    // Demoted after the page loaded — the old snapshot must stop working.
    Livewire::flushState();
    $admin->forceFill(['role' => UserRole::Volunteer->value])->save();

    $this->withHeaders(['X-Livewire' => '1'])->postJson($updateUri, $payload)->assertForbidden();
});

it('refuses to run the demo DatabaseSeeder in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new DatabaseSeeder)->run())
        ->toThrow(RuntimeException::class, 'AdminUserSeeder');

    expect(User::query()->count())->toBe(0);
});

it('seeds demo users with a forced password change and never resets existing passwords', function () {
    $existing = User::factory()->owner()->create([
        'email' => 'superadmin@fifiawoto.test',
        'password' => Hash::make('my-real-password'),
    ]);

    $this->seed(DatabaseSeeder::class);

    expect(Hash::check('my-real-password', $existing->refresh()->password))->toBeTrue()
        ->and(User::query()->where('email', 'staff@fifiawoto.test')->sole()->force_password_change)->toBeTrue();
});

it('does not trust X-Forwarded-For from arbitrary clients by default', function () {
    Route::get('/_test/client-ip', fn () => request()->ip());

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeaders(['X-Forwarded-For' => '198.51.100.99'])
        ->get('/_test/client-ip')
        ->assertSeeText('203.0.113.10');
});

it('trusts X-Forwarded-For from proxies listed in TRUSTED_PROXIES', function () {
    config(['trustedproxy.proxies' => '203.0.113.10']);
    Route::get('/_test/client-ip', fn () => request()->ip());

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeaders(['X-Forwarded-For' => '198.51.100.99'])
        ->get('/_test/client-ip')
        ->assertSeeText('198.51.100.99');
})->after(fn () => TrustProxies::flushState());

it('answers unknown emails on forgot-password exactly like known ones', function () {
    Notification::fake();
    $user = User::factory()->create();

    $known = $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email]);
    $unknown = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'nobody@example.test']);

    $known->assertRedirect(route('password.request'))->assertSessionHasNoErrors();
    $unknown->assertRedirect(route('password.request'))->assertSessionHasNoErrors();
    expect($unknown->getSession()->get('status'))->toBe($known->getSession()->get('status'));
    Notification::assertSentTo($user, ResetPassword::class);
});

it('throttles forgot-password requests', function () {
    Notification::fake();

    foreach (range(1, 3) as $attempt) {
        $this->post(route('password.email'), ['email' => 'target@example.test'])->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => 'target@example.test'])->assertTooManyRequests();
});

it('does not render links injected into markdown mail', function () {
    $html = (new ContactFormReceived(
        senderName: 'Mallory',
        senderEmail: 'mallory@example.test',
        senderPhone: '',
        subjectLine: 'Hello',
        messageBody: 'Please [verify your account](https://evil.example) now.',
    ))->render();

    expect($html)->not->toContain('href="https://evil.example"');
});

it('restricts Livewire temporary uploads to the allowed types and sizes', function () {
    $rules = config('livewire.temporary_file_upload.rules');

    expect($rules)->toContain('file', 'max:20480')
        ->and(collect($rules)->first(fn ($rule) => str_starts_with($rule, 'mimes:')))
        ->toContain('pdf', 'jpg', 'png', 'webp', 'docx')
        ->not->toContain('php')
        ->and(config('livewire.temporary_file_upload.middleware'))->toBe('throttle:20,1');
});
