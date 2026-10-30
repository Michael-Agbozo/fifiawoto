<?php

use App\Models\Beneficiary;
use App\Models\SponsorshipPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function galleryChild(array $attributes = []): Beneficiary
{
    return Beneficiary::factory()->active()->create(array_merge([
        'date_of_birth' => today()->subYears(9),
        'public_display_name' => 'Ama', 'public_story' => 'Ama enjoys reading.',
        'public_country' => 'Ghana', 'public_photo_path' => 'sponsorship/approved.jpg',
        'guardian_consent_at' => now(), 'guardian_consent_reference' => 'Signed consent record',
        'sponsorship_published' => true,
    ], $attributes));
}

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('uploads gallery photos and can mark them sensitive', function () {
    $child = galleryChild();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->set('galleryUploads', [UploadedFile::fake()->image('wound.jpg'), UploadedFile::fake()->image('home.jpg')])
        ->set('galleryUploadsSensitive', true)
        ->call('uploadGalleryPhotos')
        ->assertHasNoErrors()
        ->assertSet('galleryUploads', [])
        ->assertSet('flashMessage', '2 gallery photos added.');

    $photos = $child->sponsorshipPhotos()->get();
    expect($photos)->toHaveCount(2)
        ->and($photos->every->is_sensitive)->toBeTrue()
        ->and($photos->pluck('sort')->all())->toBe([1, 2]);
    $photos->each(fn ($photo) => Storage::disk('public')->assertExists($photo->path));
});

it('rejects files that are not images', function () {
    $child = galleryChild();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->set('galleryUploads', [UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf')])
        ->call('uploadGalleryPhotos')
        ->assertHasErrors('galleryUploads.0');

    expect($child->sponsorshipPhotos()->count())->toBe(0);
});

it('stops at twelve photos per child', function () {
    $child = galleryChild();
    SponsorshipPhoto::factory()->count(SponsorshipPhoto::MAX_PER_CHILD)->for($child)->create();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->set('galleryUploads', [UploadedFile::fake()->image('extra.jpg')])
        ->call('uploadGalleryPhotos')
        ->assertHasErrors('galleryUploads');

    expect($child->sponsorshipPhotos()->count())->toBe(SponsorshipPhoto::MAX_PER_CHILD);
});

it('toggles sensitivity, saves captions and removes photos', function () {
    $child = galleryChild();
    Storage::disk('public')->put('sponsorship/gallery/one.jpg', 'x');
    $photo = SponsorshipPhoto::factory()->for($child)->create(['path' => 'sponsorship/gallery/one.jpg', 'caption' => null]);

    $component = Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->call('toggleGallerySensitive', $photo->id)
        ->call('saveGalleryCaption', $photo->id, '  The family home after the rains  ');

    expect($photo->fresh())
        ->is_sensitive->toBeTrue()
        ->caption->toBe('The family home after the rains');

    $component->call('deleteGalleryPhoto', $photo->id)->assertSet('flashMessage', 'Gallery photo removed.');

    expect(SponsorshipPhoto::query()->find($photo->id))->toBeNull();
    Storage::disk('public')->assertMissing('sponsorship/gallery/one.jpg');
});

it('cannot change another child’s gallery photos', function () {
    $child = galleryChild();
    $otherPhoto = SponsorshipPhoto::factory()->for(galleryChild(['public_display_name' => 'Kofi']))->create();

    $component = Livewire::test('admin.beneficiary-show', ['beneficiary' => $child]);

    $component->call('deleteGalleryPhoto', $otherPhoto->id)->assertNotFound();
    $component->call('toggleGallerySensitive', $otherPhoto->id)->assertNotFound();
    expect($otherPhoto->fresh())->not->toBeNull()->is_sensitive->toBeFalse();
});

it('requires update permission to manage the gallery', function () {
    $child = galleryChild();
    $photo = SponsorshipPhoto::factory()->for($child)->create();
    $this->actingAs(User::factory()->mediaManager()->create());

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])->assertForbidden();

    expect($photo->fresh())->not->toBeNull();
});

it('shows the gallery on the public profile with sensitive photos covered', function () {
    $child = galleryChild();
    SponsorshipPhoto::factory()->for($child)->create(['caption' => 'Ama at school', 'sort' => 1]);
    SponsorshipPhoto::factory()->sensitive()->for($child)->create(['caption' => 'Treatment for her leg', 'sort' => 2]);

    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))
        ->assertOk()
        ->assertSee('Ama’s world in pictures', escape: false)
        ->assertSeeInOrder(['Ama at school', 'Treatment for her leg'])
        ->assertSee('Sensitive image')
        ->assertSee('View image')
        ->assertSee('They stay blurred until you choose to view them.');
});

it('leaves the gallery out when a child has no photos', function () {
    $child = galleryChild();

    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))
        ->assertOk()
        ->assertDontSee('id="gallery-heading"', escape: false);
});
