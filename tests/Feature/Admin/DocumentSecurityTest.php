<?php

use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDocument;
use App\Models\BeneficiaryFolder;
use App\Models\User;
use App\Support\SecureDocumentStore;
use App\Support\SimpleXlsx;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('beneficiaries');
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->beneficiary = Beneficiary::factory()->create();
});

function readEncrypted(string $path): string
{
    $stream = Storage::disk('beneficiaries')->readStream($path);
    $out = '';
    foreach (app(SecureDocumentStore::class)->decrypt($stream) as $chunk) {
        $out .= $chunk;
    }
    fclose($stream);

    return $out;
}

it('stores uploads encrypted and serves the original back', function () {
    Livewire::test('admin.beneficiary-show', ['beneficiary' => $this->beneficiary])
        ->set('documentUploads', [UploadedFile::fake()->createWithContent('medical.txt', 'PRIVATE MEDICAL NOTE')])
        ->call('uploadDocuments')
        ->assertHasNoErrors();

    $document = BeneficiaryDocument::query()->sole();
    expect($document->encrypted_at)->not->toBeNull()
        ->and($document->scan_status)->toBe(SecureDocumentStore::SCAN_NOT_SCANNED)
        ->and(Storage::disk('beneficiaries')->get($document->path))->not->toContain('PRIVATE MEDICAL NOTE');

    $response = $this->get(DocumentDownloadController::beneficiaryDocumentUrl($document, true));

    $response->assertOk();
    expect($response->streamedContent())->toBe('PRIVATE MEDICAL NOTE');
});

it('handles files larger than one chunk', function () {
    $content = random_bytes(2621440);
    $path = app(SecureDocumentStore::class)->store(UploadedFile::fake()->createWithContent('big.pdf', $content), 'x');

    expect(readEncrypted($path))->toBe($content);
});

it('detects a truncated encrypted file', function () {
    $content = random_bytes(2621440);
    $path = app(SecureDocumentStore::class)->store(UploadedFile::fake()->createWithContent('big.pdf', $content), 'x');
    $disk = Storage::disk('beneficiaries');
    $bytes = $disk->get($path);
    $firstLength = unpack('N', substr($bytes, 0, 4))[1];
    $disk->put($path, substr($bytes, 0, 4 + $firstLength));

    expect(fn () => readEncrypted($path))->toThrow(RuntimeException::class);
});

it('encrypts existing plain documents with the command', function () {
    Storage::disk('beneficiaries')->put('1/documents/old.txt', 'OLD PLAIN FILE');
    $document = BeneficiaryDocument::factory()->create(['beneficiary_id' => $this->beneficiary->id, 'disk' => 'beneficiaries', 'path' => '1/documents/old.txt', 'original_name' => 'old.txt', 'mime_type' => 'text/plain', 'size_bytes' => 14, 'encrypted_at' => null]);

    $this->artisan('beneficiaries:secure-documents')->assertSuccessful();

    expect($document->refresh()->encrypted_at)->not->toBeNull()
        ->and(Storage::disk('beneficiaries')->get($document->path))->not->toContain('OLD PLAIN FILE')
        ->and(readEncrypted($document->path))->toBe('OLD PLAIN FILE');
});

it('stamps a file that was encrypted just before a crash instead of encrypting it twice', function () {
    $path = app(SecureDocumentStore::class)->store(UploadedFile::fake()->createWithContent('id.txt', 'ORIGINAL'), '1/documents');
    $bytes = Storage::disk('beneficiaries')->get($path);
    $document = BeneficiaryDocument::factory()->create(['beneficiary_id' => $this->beneficiary->id, 'disk' => 'beneficiaries', 'path' => $path, 'original_name' => 'id.txt', 'mime_type' => 'text/plain', 'size_bytes' => 8, 'encrypted_at' => null]);

    $this->artisan('beneficiaries:secure-documents')->assertSuccessful();

    expect($document->refresh()->encrypted_at)->not->toBeNull()
        ->and(Storage::disk('beneficiaries')->get($path))->toBe($bytes)
        ->and(readEncrypted($path))->toBe('ORIGINAL');
});

it('still encrypts plain files, including ones the encrypted check could misread', function (string $content) {
    Storage::disk('beneficiaries')->put('1/documents/plain.bin', $content);

    expect(app(SecureDocumentStore::class)->isEncrypted('1/documents/plain.bin'))->toBeFalse()
        ->and(app(SecureDocumentStore::class)->encryptInPlace('1/documents/plain.bin'))->toBeTrue()
        ->and(readEncrypted('1/documents/plain.bin'))->toBe($content);
})->with([
    'small header value' => [pack('N', 5).'hello'],
    'shorter than a header' => ['ab'],
    'pdf' => ['%PDF-1.4 secret'],
]);

it('deletes the temporary upload once documents are saved', function () {
    Livewire::test('admin.beneficiary-show', ['beneficiary' => $this->beneficiary])
        ->set('documentUploads', [UploadedFile::fake()->createWithContent('id.txt', 'PLAIN')])
        ->call('uploadDocuments')
        ->assertHasNoErrors();

    expect(BeneficiaryDocument::query()->sole()->original_name)->toBe('id.txt')
        ->and(BeneficiaryDocument::query()->sole()->size_bytes)->toBe(5)
        ->and(Storage::disk('tmp-for-tests')->allFiles('livewire-tmp'))->toBeEmpty();
});

it('keeps the temporary upload when saving fails, so it can be retried', function () {
    BeneficiaryDocument::creating(fn () => throw new RuntimeException('db down'));

    expect(fn () => Livewire::test('admin.beneficiary-show', ['beneficiary' => $this->beneficiary])
        ->set('documentUploads', [UploadedFile::fake()->createWithContent('id.txt', 'PLAIN')])
        ->call('uploadDocuments'))->toThrow(RuntimeException::class);

    expect(Storage::disk('tmp-for-tests')->allFiles('livewire-tmp'))->not->toBeEmpty();
});

it('still serves documents that are not encrypted yet', function () {
    Storage::disk('beneficiaries')->put('1/documents/old.txt', 'OLD PLAIN FILE');
    $document = BeneficiaryDocument::factory()->create(['beneficiary_id' => $this->beneficiary->id, 'disk' => 'beneficiaries', 'path' => '1/documents/old.txt', 'original_name' => 'old.txt', 'mime_type' => 'text/plain', 'size_bytes' => 14, 'encrypted_at' => null]);

    $response = $this->get(DocumentDownloadController::beneficiaryDocumentUrl($document, true));

    expect($response->streamedContent())->toBe('OLD PLAIN FILE');
});

it('refuses uploads when the scanner is on but unreachable', function () {
    config(['uploads.scanner' => 'clamav', 'uploads.clamav_socket' => 'tcp://127.0.0.1:1']);

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $this->beneficiary])
        ->set('documentUploads', [UploadedFile::fake()->createWithContent('id.txt', 'x')])
        ->call('uploadDocuments')
        ->assertHasErrors('documentUploads');

    expect(BeneficiaryDocument::query()->count())->toBe(0);
});

it('rejects an infected file and accepts a clean one', function () {
    $store = new class(app('encrypter')) extends SecureDocumentStore
    {
        public string $reply = 'stream: OK';

        public function fakeScan(UploadedFile $upload): string
        {
            return $this->interpret($this->reply, $upload, 'documents');
        }
    };

    expect($store->fakeScan(UploadedFile::fake()->createWithContent('a.txt', 'x')))->toBe(SecureDocumentStore::SCAN_CLEAN);

    $store->reply = 'stream: Eicar-Test-Signature FOUND';
    expect(fn () => $store->fakeScan(UploadedFile::fake()->createWithContent('a.txt', 'x')))->toThrow(ValidationException::class);
});

it('puts a whole-folder upload into a new folder', function () {
    Livewire::test('admin.beneficiary-show', ['beneficiary' => $this->beneficiary])
        ->set('uploadFolderName', 'School 2026')
        ->set('documentUploads', [UploadedFile::fake()->createWithContent('a.txt', 'a'), UploadedFile::fake()->createWithContent('b.txt', 'b')])
        ->call('uploadDocuments')
        ->assertHasNoErrors();

    $folder = BeneficiaryFolder::query()->where('name', 'School 2026')->sole();
    expect(BeneficiaryDocument::query()->where('folder_id', $folder->id)->count())->toBe(2);
});

it('writes a valid Excel file that keeps formulas as plain text', function () {
    $path = SimpleXlsx::write('Donations: 2026', [['Name' => '=HYPERLINK("x") & <b>', 'Amount' => 12.5]]);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    unlink($path);

    $xml = simplexml_load_string($sheet);
    expect($xml)->not->toBeFalse()
        ->and($sheet)->toContain('t="inlineStr"')->toContain('<v>12.5</v>')->not->toContain('<f>');
});

it('downloads the report as Excel', function () {
    Livewire::test('admin.reports')->call('downloadExcel')->assertFileDownloaded();
});
