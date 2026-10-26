<?php

use App\Models\User;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

it('redirects a user who must enrol in 2FA to the setup page', function () {
    $user = User::factory()->create(['requires_two_factor' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('two-factor.show'))
        ->assertSessionHas('status', 'two-factor-required');
});

it('lets a user who must enrol reach the 2FA setup page without looping', function () {
    $user = User::factory()->create(['requires_two_factor' => true]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'))
        ->assertOk()
        ->assertSee('An administrator requires two-factor authentication');
});

it('lets a user who must enrol confirm their password first', function () {
    $user = User::factory()->create(['requires_two_factor' => true]);

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk();
});

it('lets Livewire updates from the 2FA page through', function () {
    $user = User::factory()->create(['requires_two_factor' => true]);

    $response = $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->postJson(EndpointResolver::updatePath(), livewireUpdateFrom('settings/two-factor'));

    expect($response->headers->get('Location'))->not->toBe(route('two-factor.show'));
});

it('blocks Livewire updates from other pages', function () {
    $user = User::factory()->create(['requires_two_factor' => true]);

    $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->postJson(EndpointResolver::updatePath(), livewireUpdateFrom('admin/users'))
        ->assertRedirect(route('two-factor.show'));
});

it('defers to the forced password change before requiring 2FA', function () {
    $user = User::factory()->create([
        'requires_two_factor' => true,
        'force_password_change' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('user-password.edit'));

    $this->actingAs($user)
        ->get(route('user-password.edit'))
        ->assertOk();
});

it('lets an enrolled user through', function () {
    $user = User::factory()->withTwoFactor()->create(['requires_two_factor' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('blocks batched Livewire updates that smuggle in a component from another page', function () {
    $user = User::factory()->create(['requires_two_factor' => true]);

    $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->postJson(EndpointResolver::updatePath(), livewireUpdateFrom('settings/two-factor', 'admin/users'))
        ->assertRedirect(route('two-factor.show'));
});

it('does not lock users out when 2FA is disabled app-wide', function () {
    config(['fortify.features' => []]);
    $user = User::factory()->create(['requires_two_factor' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('ignores a forged Livewire header on ordinary page requests', function () {
    $user = User::factory()->create(['requires_two_factor' => true]);
    $snapshot = json_encode(['memo' => ['path' => 'settings/two-factor']]);

    $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->get(route('dashboard').'?components[0][snapshot]='.urlencode($snapshot))
        ->assertRedirect(route('two-factor.show'));
});
