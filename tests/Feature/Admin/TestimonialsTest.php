<?php

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('creates a testimonial', function () {
    Livewire::test('admin.testimonials')
        ->call('startCreate')
        ->set('author_name', 'Akua Volunteer')
        ->set('author_role', 'Volunteer')
        ->set('quote', 'Being part of the foundation has changed my outlook.')
        ->set('featured', true)
        ->set('sort', 1)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $t = Testimonial::query()->sole();
    expect($t->author_name)->toBe('Akua Volunteer')
        ->and($t->featured)->toBeTrue();
});

it('validates required fields', function () {
    Livewire::test('admin.testimonials')
        ->call('startCreate')
        ->set('author_name', '')
        ->set('author_role', '')
        ->set('quote', 'short')
        ->call('save')
        ->assertHasErrors(['author_name', 'author_role', 'quote']);
});

it('updates an existing testimonial', function () {
    $t = Testimonial::factory()->create(['author_name' => 'Old Name']);

    Livewire::test('admin.testimonials')
        ->call('startEdit', $t->id)
        ->set('author_name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($t->refresh()->author_name)->toBe('New Name');
});

it('toggles featured flag', function () {
    $t = Testimonial::factory()->create(['featured' => false]);

    Livewire::test('admin.testimonials')->call('toggleFeatured', $t->id);
    expect($t->refresh()->featured)->toBeTrue();

    Livewire::test('admin.testimonials')->call('toggleFeatured', $t->id);
    expect($t->refresh()->featured)->toBeFalse();
});

it('deletes after confirmation', function () {
    $t = Testimonial::factory()->create();

    Livewire::test('admin.testimonials')
        ->call('askDelete', $t->id)
        ->call('delete', $t->id);

    expect(Testimonial::query()->whereKey($t->id)->exists())->toBeFalse();
});

it('stores an uploaded profile photo', function () {
    Storage::fake('public');

    Livewire::test('admin.testimonials')
        ->call('startCreate')
        ->set('author_name', 'Akua Volunteer')
        ->set('author_role', 'Volunteer')
        ->set('quote', 'Being part of the foundation has changed my outlook.')
        ->set('photo_upload', UploadedFile::fake()->image('akua.jpg', 600, 600))
        ->call('save')
        ->assertHasNoErrors();

    $t = Testimonial::query()->sole();
    expect($t->photo_path)->toStartWith('testimonials/');
    Storage::disk('public')->assertExists($t->photo_path);
});

it('filters featured-only', function () {
    Testimonial::factory()->create(['author_name' => 'Featured Person',  'featured' => true]);
    Testimonial::factory()->create(['author_name' => 'Regular Person',   'featured' => false]);

    Livewire::test('admin.testimonials')
        ->set('filter', 'featured')
        ->assertSee('Featured Person')
        ->assertDontSee('Regular Person');
});

it('ignores a tampered photo_path and never deletes another file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('beneficiaries/secret.jpg', 'x');
    Storage::disk('public')->put('testimonials/own.jpg', 'x');
    $t = Testimonial::factory()->create(['photo_path' => 'testimonials/own.jpg']);

    $component = Livewire::test('admin.testimonials')->call('startEdit', $t->id);

    expect(fn () => $component->set('photo_path', 'beneficiaries/secret.jpg'))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    $component->set('photo_upload', UploadedFile::fake()->image('new.jpg', 400, 400))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists('beneficiaries/secret.jpg');
    Storage::disk('public')->assertMissing('testimonials/own.jpg');
    expect($t->refresh()->photo_path)->toStartWith('testimonials/');
});
