<?php

use App\Enums\AssistanceType;
use App\Enums\BeneficiaryApplicationStatus;
use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Mail\BeneficiaryApplicationReceived;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryApplicationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('beneficiaries');
    Mail::fake();
    RateLimiter::clear('public-form:beneficiary-application:127.0.0.1');
});

function fillSupportApplication()
{
    return Livewire::test('site.beneficiary-application-form')
        ->tap(fn () => test()->travel(5)->seconds())
        ->set('full_name', 'Ama Mensah')
        ->set('phone', '+233 200 000 000')
        ->set('country', 'Ghana')
        ->set('region', 'Volta')
        ->set('assistance_type', AssistanceType::Medical->value)
        ->set('situation', 'My daughter needs surgery that our family cannot afford on our own.')
        ->set('consent', true);
}

it('renders the apply for support page', function () {
    $this->get(route('apply-for-support'))
        ->assertOk()
        ->assertSee('Apply for Support')
        ->assertSeeLivewire('site.beneficiary-application-form');
});

it('stores an application with its documents on the private disk', function () {
    fillSupportApplication()
        ->set('documents', [
            UploadedFile::fake()->create('medical-report.pdf', 200, 'application/pdf'),
            UploadedFile::fake()->image('ghana-card.jpg'),
        ])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $application = BeneficiaryApplication::query()->sole();
    expect($application->status)->toBe(BeneficiaryApplicationStatus::New)
        ->and($application->consented_at)->not->toBeNull()
        ->and($application->assistance_type)->toBe(AssistanceType::Medical)
        ->and($application->documents)->toHaveCount(2);

    foreach ($application->documents as $document) {
        expect($document->disk)->toBe('beneficiaries');
        Storage::disk('beneficiaries')->assertExists($document->path);
    }

    Mail::assertQueued(BeneficiaryApplicationReceived::class);
});

it('validates the application', function () {
    Livewire::test('site.beneficiary-application-form')
        ->set('assistance_type', 'not-a-type')
        ->set('situation', 'too short')
        ->tap(fn () => $this->travel(5)->seconds())
        ->call('submit')
        ->assertHasErrors(['full_name', 'phone', 'country', 'assistance_type', 'situation', 'consent']);

    expect(BeneficiaryApplication::query()->count())->toBe(0);
});

it('deletes the temporary uploads once the application is saved', function () {
    fillSupportApplication()
        ->set('documents', [UploadedFile::fake()->create('medical.pdf', 10, 'application/pdf')])
        ->call('submit')
        ->assertHasNoErrors();

    $document = BeneficiaryApplication::query()->sole()->documents()->sole();
    expect($document->original_name)->toBe('medical.pdf')
        ->and(Storage::disk('tmp-for-tests')->allFiles('livewire-tmp'))->toBeEmpty();
});

it('rejects unsafe or too many documents', function () {
    // Unsafe types are rejected at upload time (config/livewire.php temporary_file_upload rules)
    // or on submit — either way, nothing with that extension is ever stored.
    fillSupportApplication()
        ->set('documents', [UploadedFile::fake()->create('page.html', 1, 'text/html')])
        ->assertHasErrors('documents.0')
        ->call('submit');

    expect(BeneficiaryApplicationDocument::query()->where('original_name', 'page.html')->exists())->toBeFalse();
    BeneficiaryApplication::query()->delete();

    fillSupportApplication()
        ->set('documents', array_map(fn ($i) => UploadedFile::fake()->create("doc{$i}.pdf", 10, 'application/pdf'), range(1, 6)))
        ->call('submit')
        ->assertHasErrors('documents');

    expect(BeneficiaryApplication::query()->count())->toBe(0);
});

it('throttles repeated applications from the same IP', function () {
    fillSupportApplication()->call('submit')->assertHasNoErrors();
    fillSupportApplication()->call('submit')->assertHasNoErrors();
    fillSupportApplication()->call('submit')->assertHasNoErrors();
    fillSupportApplication()->call('submit')->assertHasErrors('rate');

    expect(BeneficiaryApplication::query()->count())->toBe(3);
});

it('carries application documents into the beneficiary profile on conversion', function () {
    fillSupportApplication()
        ->set('documents', [UploadedFile::fake()->create('medical-report.pdf', 200, 'application/pdf')])
        ->call('submit');

    $application = BeneficiaryApplication::query()->sole();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.beneficiary-applications')
        ->call('convertToBeneficiary', $application->id);

    $beneficiary = Beneficiary::query()->sole();
    $document = $beneficiary->documents()->sole();
    expect($document->original_name)->toBe('medical-report.pdf')
        ->and($document->folder->name)->toBe('Identification Documents');
    Storage::disk('beneficiaries')->assertExists($document->path);
    Storage::disk('beneficiaries')->assertExists($application->documents()->sole()->path);
});

it('removes stored files when an application is deleted', function () {
    fillSupportApplication()
        ->set('documents', [UploadedFile::fake()->create('medical-report.pdf', 200, 'application/pdf')])
        ->call('submit');

    $application = BeneficiaryApplication::query()->sole();
    $path = $application->documents()->sole()->path;
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.beneficiary-applications')->call('delete', $application->id);

    expect(BeneficiaryApplication::query()->count())->toBe(0);
    Storage::disk('beneficiaries')->assertMissing($path);
});

it('serves application documents only through signed links to permitted admins', function () {
    fillSupportApplication()
        ->set('documents', [UploadedFile::fake()->create('medical-report.pdf', 200, 'application/pdf')])
        ->call('submit');

    $document = BeneficiaryApplication::query()->sole()->documents()->sole();
    $url = DocumentDownloadController::applicationDocumentUrl($document);

    $this->actingAs(User::factory()->foundationStaff()->create());
    $this->get($url)->assertOk();

    $this->actingAs(User::factory()->volunteerCoordinator()->create());
    $this->get($url)->assertForbidden();
});

it('converts an application only once even if the action is repeated', function () {
    fillSupportApplication()->call('submit');
    $application = BeneficiaryApplication::query()->sole();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.beneficiary-applications')->call('convertToBeneficiary', $application->id);
    Livewire::test('admin.beneficiary-applications')
        ->call('convertToBeneficiary', $application->id)
        ->assertSet('flashMessage', 'Application already converted.');

    expect(Beneficiary::query()->count())->toBe(1);
});
