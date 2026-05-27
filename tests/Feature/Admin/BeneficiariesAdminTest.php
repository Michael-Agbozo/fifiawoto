<?php

use App\Enums\AssistanceType;
use App\Enums\BeneficiaryApplicationStatus;
use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\User;
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
