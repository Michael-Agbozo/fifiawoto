<?php

use App\Models\User;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

it('redirects a user who must change their password to the password page', function () {
    $user = User::factory()->create(['force_password_change' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('user-password.edit'))
        ->assertSessionHas('force_password_change', true);
});

it('lets a user who must change their password reach the password page', function () {
    $user = User::factory()->create(['force_password_change' => true]);

    $this->actingAs($user)
        ->get(route('user-password.edit'))
        ->assertOk();
});

it('lets Livewire updates from the password page through', function () {
    $user = User::factory()->create(['force_password_change' => true]);

    $response = $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->postJson(EndpointResolver::updatePath(), livewireUpdateFrom('settings/password'));

    expect($response->headers->get('Location'))->not->toBe(route('user-password.edit'));
});

it('blocks Livewire updates from other pages', function () {
    $user = User::factory()->create(['force_password_change' => true]);

    $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->postJson(EndpointResolver::updatePath(), livewireUpdateFrom('admin/users'))
        ->assertRedirect(route('user-password.edit'));
});

it('blocks batched Livewire updates that smuggle in a component from another page', function () {
    $user = User::factory()->create(['force_password_change' => true]);

    $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->postJson(EndpointResolver::updatePath(), livewireUpdateFrom('settings/password', 'admin/users'))
        ->assertRedirect(route('user-password.edit'));
});

it('ignores a forged Livewire header on ordinary page requests', function () {
    $user = User::factory()->create(['force_password_change' => true]);
    $snapshot = json_encode(['memo' => ['path' => 'settings/password']]);

    $this->actingAs($user)
        ->withHeader('X-Livewire', 'true')
        ->get(route('dashboard').'?components[0][snapshot]='.urlencode($snapshot))
        ->assertRedirect(route('user-password.edit'));
});
