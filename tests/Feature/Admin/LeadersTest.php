<?php

use App\Models\Leader;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('creates a leader', function () {
    Livewire::test('admin.leaders')
        ->call('startCreate')
        ->set('name', 'Akua Asare')
        ->set('role', 'Board of Directors')
        ->set('sort', 5)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $leader = Leader::query()->sole();
    expect($leader->name)->toBe('Akua Asare')
        ->and($leader->role)->toBe('Board of Directors')
        ->and($leader->is_published)->toBeTrue();
});

it('validates required fields', function () {
    Livewire::test('admin.leaders')
        ->call('startCreate')
        ->set('name', '')
        ->set('role', '')
        ->call('save')
        ->assertHasErrors(['name', 'role']);
});

it('updates an existing leader', function () {
    $leader = Leader::factory()->create(['name' => 'Old Name', 'role' => 'Board of Advisors']);

    Livewire::test('admin.leaders')
        ->call('startEdit', $leader->id)
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($leader->refresh()->name)->toBe('New Name');
});

it('toggles published flag', function () {
    $leader = Leader::factory()->create(['is_published' => true]);

    Livewire::test('admin.leaders')->call('togglePublished', $leader->id);
    expect($leader->refresh()->is_published)->toBeFalse();

    Livewire::test('admin.leaders')->call('togglePublished', $leader->id);
    expect($leader->refresh()->is_published)->toBeTrue();
});

it('deletes after confirmation', function () {
    $leader = Leader::factory()->create();

    Livewire::test('admin.leaders')
        ->call('askDelete', $leader->id)
        ->call('delete', $leader->id);

    expect(Leader::query()->whereKey($leader->id)->exists())->toBeFalse();
});

it('stores an uploaded profile photo under the leaders disk path', function () {
    Storage::fake('public');

    Livewire::test('admin.leaders')
        ->call('startCreate')
        ->set('name', 'Akua Asare')
        ->set('role', 'Board of Directors')
        ->set('photo_upload', UploadedFile::fake()->image('akua.jpg', 600, 600))
        ->call('save')
        ->assertHasNoErrors();

    $leader = Leader::query()->sole();
    expect($leader->photo_path)->toStartWith('leaders/');
    Storage::disk('public')->assertExists($leader->photo_path);
});

it('filters hidden leaders', function () {
    Leader::factory()->create(['name' => 'Visible Person', 'is_published' => true]);
    Leader::factory()->unpublished()->create(['name' => 'Hidden Person']);

    Livewire::test('admin.leaders')
        ->set('filter', 'hidden')
        ->assertSee('Hidden Person')
        ->assertDontSee('Visible Person');
});

it('shows published leaders on the public about page', function () {
    Leader::factory()->create([
        'name' => 'Public Leader',
        'role' => 'Founder · Board Chair',
        'sort' => 0,
        'is_published' => true,
    ]);
    Leader::factory()->unpublished()->create([
        'name' => 'Hidden Leader',
        'sort' => 1,
    ]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Public Leader')
        ->assertSee('Founder · Board Chair', escape: false)
        ->assertDontSee('Hidden Leader');
});
