<?php

use App\Models\Beneficiary;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('links a beneficiary to their Google Drive folder and shows an open button', function () {
    $beneficiary = Beneficiary::factory()->create();
    $link = 'https://drive.google.com/drive/folders/1AbCdEfGhIjK';

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->set('driveFolderUrl', '  '.$link.'  ')
        ->call('saveDriveFolder')
        ->assertHasNoErrors()
        ->assertSet('flashMessage', 'Google Drive folder linked.')
        ->assertDispatched('drive-folder-saved')
        ->assertSee('Open Drive folder')
        ->assertSee($link, escape: false);

    expect($beneficiary->fresh()->drive_folder_url)->toBe($link);
});

it('rejects links that are not Google Drive', function (string $link) {
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->set('driveFolderUrl', $link)
        ->call('saveDriveFolder')
        ->assertHasErrors('driveFolderUrl');

    expect($beneficiary->fresh()->drive_folder_url)->toBeNull();
})->with([
    'another website' => 'https://example.com/drive/folders/1',
    'look-alike host' => 'https://drive.google.com.evil.test/folders/1',
    'plain http' => 'http://drive.google.com/drive/folders/1',
    'not a link' => 'my drive folder',
]);

it('removes the link when the field is cleared', function () {
    $beneficiary = Beneficiary::factory()->create(['drive_folder_url' => 'https://drive.google.com/drive/folders/old']);

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->set('driveFolderUrl', '')
        ->call('saveDriveFolder')
        ->assertSet('flashMessage', 'Google Drive folder link removed.');

    expect($beneficiary->fresh()->drive_folder_url)->toBeNull();
});

it('only lets staff who can update beneficiaries change the link', function () {
    $beneficiary = Beneficiary::factory()->create();
    $this->actingAs(User::factory()->mediaManager()->create());

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])->assertForbidden();
});

it('saves the Drive link from the add and edit form and shows it in the list', function () {
    $beneficiary = Beneficiary::factory()->create(['full_name' => 'Efua Mensah']);
    $link = 'https://drive.google.com/drive/folders/EfuaFolder';

    Livewire::test('admin.beneficiaries')
        ->call('startEdit', $beneficiary->id)
        ->set('drive_folder_url', $link)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('href="'.$link.'"', escape: false)
        ->assertSee('Google Drive folder');

    expect($beneficiary->fresh()->drive_folder_url)->toBe($link);

    Livewire::test('admin.beneficiaries')
        ->call('startEdit', $beneficiary->id)
        ->assertSet('drive_folder_url', $link)
        ->set('drive_folder_url', 'https://dropbox.com/folder')
        ->call('save')
        ->assertHasErrors('drive_folder_url');
});
