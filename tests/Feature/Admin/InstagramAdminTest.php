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

it('re-adding an existing post keeps its posted_at and hidden flag', function () {
    $post = InstagramPost::factory()->create([
        'external_id' => sha1('https://instagram.com/p/keepme'),
        'permalink' => 'https://instagram.com/p/keepme',
        'posted_at' => now()->subMonths(3)->startOfSecond(),
        'is_hidden' => true,
    ]);
    $originalPostedAt = $post->posted_at;

    Livewire::test('admin.instagram')
        ->call('startCreate')
        ->set('permalink', 'https://instagram.com/p/keepme')
        ->set('caption', 'Updated caption')
        ->call('add')
        ->assertHasNoErrors();

    $post->refresh();
    expect(InstagramPost::query()->count())->toBe(1)
        ->and($post->caption)->toBe('Updated caption')
        ->and($post->is_hidden)->toBeTrue()
        ->and($post->posted_at->equalTo($originalPostedAt))->toBeTrue();
});

it('shows media_url for image posts that have no thumbnail', function () {
    InstagramPost::factory()->create([
        'media_type' => 'IMAGE',
        'thumbnail_url' => null,
        'media_url' => 'https://cdn.example.com/image-only.jpg',
    ]);

    Livewire::test('admin.instagram')->assertSeeHtml('https://cdn.example.com/image-only.jpg');
});
