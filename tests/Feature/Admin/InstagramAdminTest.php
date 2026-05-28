<?php

use App\Models\InstagramPost;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create()));

it('adds an Instagram post via permalink', function () {
    Livewire::test('admin.instagram')
        ->call('startCreate')
        ->set('permalink', 'https://instagram.com/p/abc123')
        ->set('caption', 'Volta outreach day')
        ->call('add')
        ->assertHasNoErrors();

    expect(InstagramPost::query()->count())->toBe(1);
});

it('toggles visibility', function () {
    $p = InstagramPost::factory()->create(['is_hidden' => false]);

    Livewire::test('admin.instagram')->call('toggleHide', $p->id);
    expect($p->refresh()->is_hidden)->toBeTrue();
});
