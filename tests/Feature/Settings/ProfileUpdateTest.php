<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('the last active owner cannot delete their own account', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect($owner->fresh())->not->toBeNull();
    expect(auth()->check())->toBeTrue();
});

test('an owner can delete their account while another owner remains active', function () {
    User::factory()->owner()->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner);

    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors();

    expect($owner->fresh())->toBeNull();
});

test('profile picture can be uploaded and replaces the old one', function () {
    Storage::fake('public');
    $user = User::factory()->create(['avatar_path' => 'avatars/old.jpg']);
    Storage::disk('public')->put('avatars/old.jpg', 'old');
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('photo', UploadedFile::fake()->image('me.png'))
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->avatar_path)->toStartWith('avatars/')->not->toBe('avatars/old.jpg');
    Storage::disk('public')->assertExists($user->avatar_path);
    Storage::disk('public')->assertMissing('avatars/old.jpg');
});

test('profile picture must be a small image', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.profile')
        ->set('photo', UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'))
        ->call('updateProfileInformation')
        ->assertHasErrors(['photo' => 'image']);

    Livewire::test('pages::settings.profile')
        ->set('photo', UploadedFile::fake()->image('huge.jpg')->size(3000))
        ->call('updateProfileInformation')
        ->assertHasErrors(['photo' => 'max']);
});

test('profile picture can be removed', function () {
    Storage::fake('public');
    $user = User::factory()->create(['avatar_path' => 'avatars/me.jpg']);
    Storage::disk('public')->put('avatars/me.jpg', 'me');
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')->call('removeAvatar');

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/me.jpg');
});

test('profile page no longer offers account deletion', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('data-test="delete-user-button"', false)
        ->assertDontSee('Delete account');
});
