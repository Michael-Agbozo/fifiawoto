<?php

use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDocument;
use App\Models\BeneficiaryFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('beneficiaries');
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());
});

function storedBeneficiaryDocument(Beneficiary $beneficiary, array $overrides = []): BeneficiaryDocument
{
    $path = $overrides['path'] ?? "{$beneficiary->id}/documents/id-card.pdf";
    Storage::disk($overrides['disk'] ?? 'beneficiaries')->put($path, '%PDF-1.4 secret');

    return BeneficiaryDocument::query()->create([
        'beneficiary_id' => $beneficiary->id,
        'disk' => 'beneficiaries',
        'path' => $path,
        'original_name' => 'id-card.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 15,
        ...$overrides,
    ]);
}

it('stores uploads on the private beneficiaries disk', function () {
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->set('documentUploads', [UploadedFile::fake()->create('report.pdf', 50, 'application/pdf')])
        ->call('uploadDocuments')
        ->assertHasNoErrors();

    $document = $beneficiary->documents()->sole();
    expect($document->disk)->toBe('beneficiaries');
    Storage::disk('beneficiaries')->assertExists($document->path);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('rejects file types a browser would execute', function () {
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->set('documentUploads', [UploadedFile::fake()->create('evil.html', 1, 'text/html')])
        ->call('uploadDocuments')
        ->assertHasErrors('documentUploads.0');

    expect($beneficiary->documents()->count())->toBe(0);
});

it('serves a document through a signed link', function () {
    $document = storedBeneficiaryDocument(Beneficiary::factory()->create());

    $this->get(DocumentDownloadController::beneficiaryDocumentUrl($document))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=id-card.pdf');

    $this->get(DocumentDownloadController::beneficiaryDocumentUrl($document, download: true))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=id-card.pdf');
});

it('rejects unsigned, expired and tampered links', function () {
    $document = storedBeneficiaryDocument(Beneficiary::factory()->create());

    $this->get(route('admin.beneficiaries.documents.show', [$document->beneficiary_id, $document->id]))
        ->assertForbidden();

    $url = DocumentDownloadController::beneficiaryDocumentUrl($document);
    $this->travel(DocumentDownloadController::LINK_TTL_MINUTES + 1)->minutes();
    $this->get($url)->assertForbidden();
});

it('refuses a document requested under the wrong beneficiary', function () {
    $document = storedBeneficiaryDocument(Beneficiary::factory()->create());
    $other = Beneficiary::factory()->create();

    $url = URL::temporarySignedRoute('admin.beneficiaries.documents.show', now()->addMinutes(5), [
        'beneficiary' => $other->id, 'document' => $document->id,
    ]);

    $this->get($url)->assertNotFound();
});

it('requires the beneficiaries.view permission even with a valid link', function () {
    $document = storedBeneficiaryDocument(Beneficiary::factory()->create());
    $url = DocumentDownloadController::beneficiaryDocumentUrl($document);

    $this->actingAs(User::factory()->mediaManager()->create());
    $this->get($url)->assertForbidden();

    auth()->logout();
    $this->get($url)->assertRedirect(route('login'));
});

it('only deletes documents and folders that belong to the open beneficiary', function () {
    $beneficiary = Beneficiary::factory()->create();
    $otherDocument = storedBeneficiaryDocument(Beneficiary::factory()->create());
    $otherFolder = BeneficiaryFolder::query()->create([
        'beneficiary_id' => $otherDocument->beneficiary_id, 'name' => 'Medical', 'slug' => 'medical',
    ]);

    expect(fn () => Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->call('deleteDocument', $otherDocument->id))->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->call('deleteFolder', $otherFolder->id))->toThrow(ModelNotFoundException::class);

    expect($otherDocument->fresh())->not->toBeNull()
        ->and($otherFolder->fresh())->not->toBeNull();
});

it('lets view-only roles open the profile but not change documents', function () {
    $beneficiary = Beneficiary::factory()->create();
    $document = storedBeneficiaryDocument($beneficiary);
    $this->actingAs(User::factory()->volunteerCoordinator()->create());

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->assertOk()
        ->call('deleteDocument', $document->id)
        ->assertForbidden();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->set('documentUploads', [UploadedFile::fake()->create('report.pdf', 50, 'application/pdf')])
        ->call('uploadDocuments')
        ->assertForbidden();

    expect($document->fresh())->not->toBeNull()
        ->and($beneficiary->documents()->count())->toBe(1);
});

it('hides the profile from roles without beneficiaries.view', function () {
    $beneficiary = Beneficiary::factory()->create();
    $this->actingAs(User::factory()->mediaManager()->create());

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])->assertForbidden();
});

it('moves legacy public-disk documents onto the private disk', function () {
    $beneficiary = Beneficiary::factory()->create();
    $document = storedBeneficiaryDocument($beneficiary, [
        'disk' => 'public',
        'path' => "beneficiaries/{$beneficiary->id}/documents/old.pdf",
    ]);

    $this->artisan('beneficiaries:secure-documents')->assertSuccessful();

    $document->refresh();
    expect($document->disk)->toBe('beneficiaries')
        ->and($document->path)->toBe("{$beneficiary->id}/documents/old.pdf");
    Storage::disk('beneficiaries')->assertExists($document->path);
    Storage::disk('public')->assertMissing("beneficiaries/{$beneficiary->id}/documents/old.pdf");
});

it('keeps the original when the copy to the private disk fails', function () {
    $beneficiary = Beneficiary::factory()->create();
    $document = storedBeneficiaryDocument($beneficiary, [
        'disk' => 'public',
        'path' => "beneficiaries/{$beneficiary->id}/documents/old.pdf",
    ]);

    $failingDisk = Mockery::mock(Storage::disk('beneficiaries'))->makePartial();
    $failingDisk->shouldReceive('writeStream')->andReturnFalse();
    Storage::set('beneficiaries', $failingDisk);

    $this->artisan('beneficiaries:secure-documents')->assertFailed();

    expect($document->refresh()->disk)->toBe('public');
    Storage::disk('public')->assertExists("beneficiaries/{$beneficiary->id}/documents/old.pdf");
});
