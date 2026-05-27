<?php

use App\Enums\MediaCategory;
use App\Models\MediaItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create()));

it('adds a media item', function () {
    Livewire::test('admin.media')
        ->call('startCreate')
        ->set('path', 'media/outreach/photo.jpg')
        ->set('category', MediaCategory::Events->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(MediaItem::query()->count())->toBe(1);
});

it('filters by category', function () {
    MediaItem::factory()->create(['path' => 'a/x.jpg', 'caption' => 'Event Pic',      'category' => MediaCategory::Events->value]);
    MediaItem::factory()->create(['path' => 'b/y.jpg', 'caption' => 'Volunteer Pic',  'category' => MediaCategory::Volunteers->value]);

    Livewire::test('admin.media')
        ->set('categoryFilter', MediaCategory::Events->value)
        ->assertSee('Event Pic')
        ->assertDontSee('Volunteer Pic');
});
