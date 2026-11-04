<?php

use App\Enums\AssistanceType;
use App\Enums\BeneficiaryApplicationStatus;
use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryFolder;
use App\Models\BeneficiaryTimelineEntry;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->superAdmin()->create()));

it('creates a beneficiary record and seeds default folders + timeline', function () {
    Livewire::test('admin.beneficiaries')
        ->call('startCreate')
        ->set('full_name', 'Akua Beneficiary')
        ->set('country', 'Ghana')
        ->set('category', SupportCategory::WidowSupport->value)
        ->set('description', 'A long-term widow support case in the Volta Region.')
        ->set('status', SupportStatus::PendingReview->value)
        ->call('save')
        ->assertHasNoErrors();

    $b = Beneficiary::query()->where('full_name', 'Akua Beneficiary')->sole();
    expect($b->folders()->count())->toBe(5)
        ->and($b->timeline()->count())->toBe(1);
});

it('updates the status from the profile page and writes a timeline entry', function () {
    $b = Beneficiary::factory()->create(['status' => SupportStatus::PendingReview->value]);

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $b])
        ->call('setStatus', SupportStatus::Approved->value);

    expect($b->refresh()->status)->toBe(SupportStatus::Approved)
        ->and($b->timeline()->count())->toBe(1);
});

it('converts an application into a beneficiary record', function () {
    $app = BeneficiaryApplication::factory()->create([
        'status' => BeneficiaryApplicationStatus::New->value,
        'assistance_type' => AssistanceType::Education->value,
    ]);

    Livewire::test('admin.beneficiary-applications')
        ->call('convertToBeneficiary', $app->id);

    $app->refresh();
    expect($app->status)->toBe(BeneficiaryApplicationStatus::Approved)
        ->and($app->converted_beneficiary_id)->not->toBeNull();

    $b = Beneficiary::query()->find($app->converted_beneficiary_id);
    expect($b)->not->toBeNull()
        ->and($b->category)->toBe(SupportCategory::ChildEducation)
        ->and($b->folders()->count())->toBe(5);
});

it('creates a folder on the profile page', function () {
    $b = Beneficiary::factory()->create();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $b])
        ->call('startCreateFolder')
        ->set('newFolderName', 'Custom Folder')
        ->call('createFolder');

    expect($b->folders()->where('name', 'Custom Folder')->exists())->toBeTrue();
});

it('rejects invalid category and status values instead of saving them', function () {
    Livewire::test('admin.beneficiaries')
        ->call('startCreate')
        ->set('full_name', 'Bad Enum')
        ->set('country', 'Ghana')
        ->set('category', 'not-a-category')
        ->set('status', 'not-a-status')
        ->set('description', 'A long-term widow support case in the Volta Region.')
        ->call('save')
        ->assertHasErrors(['category', 'status']);

    expect(Beneficiary::query()->count())->toBe(0);
});

it('ignores a tampered photo_path and only deletes the record\'s own photo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('leaders/someone-else.jpg', 'x');
    Storage::disk('public')->put('beneficiaries/own.jpg', 'x');
    $b = Beneficiary::factory()->create(['photo_path' => 'beneficiaries/own.jpg']);

    $component = Livewire::test('admin.beneficiaries')->call('startEdit', $b->id);

    expect(fn () => $component->set('photo_path', 'leaders/someone-else.jpg'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => $component->set('editingId', 999))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    $component->set('remove_photo', true)->call('save')->assertHasNoErrors();

    Storage::disk('public')->assertExists('leaders/someone-else.jpg');
    Storage::disk('public')->assertMissing('beneficiaries/own.jpg');
    expect($b->refresh()->photo_path)->toBeNull();
});

it('keeps the old photo when the record update fails', function () {
    Storage::fake('public');
    Storage::disk('public')->put('beneficiaries/own.jpg', 'x');
    $b = Beneficiary::factory()->create(['photo_path' => 'beneficiaries/own.jpg']);

    Beneficiary::updating(fn () => throw new RuntimeException('db down'));

    expect(fn () => Livewire::test('admin.beneficiaries')
        ->call('startEdit', $b->id)
        ->set('remove_photo', true)
        ->call('save'))->toThrow(RuntimeException::class);

    Storage::disk('public')->assertExists('beneficiaries/own.jpg');
});

it('rolls back the new record if seeding its folders fails', function () {
    BeneficiaryTimelineEntry::creating(fn () => throw new RuntimeException('boom'));

    expect(fn () => Livewire::test('admin.beneficiaries')
        ->call('startCreate')
        ->set('full_name', 'Half Made')
        ->set('country', 'Ghana')
        ->set('category', SupportCategory::WidowSupport->value)
        ->set('description', 'A long-term widow support case in the Volta Region.')
        ->call('save'))->toThrow(RuntimeException::class);

    expect(Beneficiary::withTrashed()->count())->toBe(0)
        ->and(BeneficiaryFolder::query()->count())->toBe(0);
});

it('refuses status changes on a converted application and hides the row actions', function () {
    $app = BeneficiaryApplication::factory()->create([
        'full_name' => 'Converted Applicant',
        'status' => BeneficiaryApplicationStatus::New->value,
        'assistance_type' => AssistanceType::Education->value,
    ]);

    $component = Livewire::test('admin.beneficiary-applications')
        ->set('statusFilter', '')
        ->call('convertToBeneficiary', $app->id);

    $component->call('setStatus', $app->id, BeneficiaryApplicationStatus::Rejected->value);

    expect($app->refresh()->status)->toBe(BeneficiaryApplicationStatus::Approved);

    $component->call('$refresh')
        ->assertDontSeeHtml("setStatus({$app->id}, 'rejected')")
        ->assertDontSeeHtml("setStatus({$app->id}, 'under_review')");
});
