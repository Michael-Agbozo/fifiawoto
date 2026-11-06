<?php

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('beneficiaries');
    $this->actingAs(User::factory()->superAdmin()->create());
});

function privatePhotoBeneficiary(): Beneficiary
{
    Storage::disk('beneficiaries')->put('photos/face.jpg', UploadedFile::fake()->image('face.jpg')->get());

    return Beneficiary::factory()->create(['photo_path' => 'photos/face.jpg', 'photo_disk' => 'beneficiaries']);
}

function legacyPhotoBeneficiary(string $path = 'beneficiaries/face.jpg'): Beneficiary
{
    Storage::disk('public')->put($path, UploadedFile::fake()->image('face.jpg')->get());

    return Beneficiary::factory()->create(['photo_path' => $path]);
}

it('stores a new photo on the private disk and clears the temporary upload', function () {
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test('admin.beneficiaries')
        ->call('startEdit', $beneficiary->id)
        ->set('photo_upload', UploadedFile::fake()->image('face.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $beneficiary->refresh();
    expect($beneficiary->photo_disk)->toBe('beneficiaries')
        ->and(Storage::disk('public')->allFiles())->toBeEmpty()
        ->and(Storage::disk('tmp-for-tests')->allFiles('livewire-tmp'))->toBeEmpty();
    Storage::disk('beneficiaries')->assertExists($beneficiary->photo_path);
});

it('serves a private photo to staff who can view beneficiaries, with no caching', function () {
    $response = $this->get(privatePhotoBeneficiary()->adminPhotoUrl());

    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');
});

it('refuses the photo without the beneficiaries view permission', function () {
    $url = privatePhotoBeneficiary()->adminPhotoUrl();

    $this->actingAs(User::factory()->mediaManager()->create())->get($url)->assertForbidden();
});

it('refuses the photo to guests', function () {
    $url = privatePhotoBeneficiary()->adminPhotoUrl();
    auth()->logout();

    $this->get($url)->assertRedirect(route('login'));
});

it('does not serve a public-disk photo through the private route', function () {
    $this->get(route('admin.beneficiaries.photo', legacyPhotoBeneficiary()))->assertNotFound();
});

it('points avatars at the private route, and keeps photos not moved yet working', function () {
    $private = privatePhotoBeneficiary();
    $legacy = legacyPhotoBeneficiary();
    $photoRoute = route('admin.beneficiaries.photo', $private);

    expect($legacy->adminPhotoUrl())->toBe(Storage::disk('public')->url('beneficiaries/face.jpg'));

    Livewire::test('admin.beneficiaries')
        ->assertSee($photoRoute, escape: false)
        ->assertSee($legacy->adminPhotoUrl(), escape: false)
        ->call('startEdit', $private->id)
        ->assertSet('photo_url', $photoRoute);
    Livewire::test('admin.beneficiary-show', ['beneficiary' => $private])
        ->assertSee($photoRoute, escape: false);
});

it('deletes the old photo from whichever disk held it when replacing', function (Closure $makeBeneficiary, string $disk) {
    $beneficiary = $makeBeneficiary();
    $oldPath = $beneficiary->photo_path;

    Livewire::test('admin.beneficiaries')
        ->call('startEdit', $beneficiary->id)
        ->set('photo_upload', UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk($disk)->assertMissing($oldPath);
    expect($beneficiary->refresh()->photo_disk)->toBe('beneficiaries');
    Storage::disk('beneficiaries')->assertExists($beneficiary->photo_path);
})->with([
    'private' => [fn () => privatePhotoBeneficiary(), 'beneficiaries'],
    'public' => [fn () => legacyPhotoBeneficiary(), 'public'],
]);

it('deletes a private photo when it is removed', function () {
    $beneficiary = privatePhotoBeneficiary();

    Livewire::test('admin.beneficiaries')
        ->call('startEdit', $beneficiary->id)
        ->set('remove_photo', true)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('beneficiaries')->assertMissing('photos/face.jpg');
    expect($beneficiary->refresh())
        ->photo_path->toBeNull()
        ->photo_disk->toBeNull();
});

it('moves existing photos off the public disk, including archived records', function () {
    $beneficiary = legacyPhotoBeneficiary();
    $archived = legacyPhotoBeneficiary('beneficiaries/archived.jpg');
    $archived->delete();
    $bytes = Storage::disk('public')->get('beneficiaries/face.jpg');

    $this->artisan('beneficiaries:secure-documents')->assertSuccessful();

    expect($beneficiary->refresh())
        ->photo_disk->toBe('beneficiaries')
        ->photo_path->toBe('photos/face.jpg')
        ->and(Storage::disk('beneficiaries')->get('photos/face.jpg'))->toBe($bytes)
        ->and($archived->refresh()->photo_path)->toBe('photos/archived.jpg');
    Storage::disk('public')->assertMissing('beneficiaries/face.jpg');
    Storage::disk('public')->assertMissing('beneficiaries/archived.jpg');
    $this->get($beneficiary->adminPhotoUrl())->assertOk();
});

it('leaves photos alone on a dry run', function () {
    $beneficiary = legacyPhotoBeneficiary();

    $this->artisan('beneficiaries:secure-documents', ['--dry-run' => true])->assertSuccessful();

    expect($beneficiary->refresh())
        ->photo_disk->toBeNull()
        ->photo_path->toBe('beneficiaries/face.jpg');
    Storage::disk('public')->assertExists('beneficiaries/face.jpg');
    expect(Storage::disk('beneficiaries')->allFiles())->toBeEmpty();
});

it('keeps a photo working on the public disk when its delete fails, then finishes on a rerun', function () {
    $beneficiary = legacyPhotoBeneficiary();
    $workingDisk = Storage::disk('public');

    $failingDisk = Mockery::mock($workingDisk)->makePartial();
    $failingDisk->shouldReceive('delete')->andReturnFalse();
    Storage::set('public', $failingDisk);

    $this->artisan('beneficiaries:secure-documents')->assertFailed();

    expect($beneficiary->refresh()->photo_disk)->toBeNull();
    $workingDisk->assertExists('beneficiaries/face.jpg');

    Storage::set('public', $workingDisk);

    $this->artisan('beneficiaries:secure-documents')->assertSuccessful();

    expect($beneficiary->refresh()->photo_disk)->toBe('beneficiaries');
    $workingDisk->assertMissing('beneficiaries/face.jpg');
});

it('skips photo paths that are not files on the public disk', function () {
    $beneficiary = Beneficiary::factory()->create(['photo_path' => 'images/foundation/placeholder.jpg']);

    $this->artisan('beneficiaries:secure-documents')->assertSuccessful()->expectsOutput('Nothing to do.');

    expect($beneficiary->refresh()->photo_disk)->toBeNull();
});
