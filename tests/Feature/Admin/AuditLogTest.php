<?php

use App\Enums\BeneficiaryApplicationStatus;
use App\Enums\SponsorshipStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Admin\DocumentDownloadController;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryDocument;
use App\Models\Donation;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Models\Sponsorship;
use App\Models\User;
use App\Support\Audit;
use App\Support\InstagramToken;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

function auditEntry(string $action): AuditLog
{
    return AuditLog::query()->where('action', $action)->sole();
}

function auditJson(): string
{
    return AuditLog::query()->get()->map(fn (AuditLog $entry) => json_encode($entry->properties).' '.$entry->description)->implode("\n");
}

it('records role and permission changes with the acting admin', function () {
    $owner = User::factory()->owner()->create();
    $staff = User::factory()->foundationStaff()->create();
    $this->actingAs($owner);

    Livewire::test('admin.users')
        ->call('startEditRole', $staff->id)
        ->set('editRoleValue', UserRole::MediaManager->value)
        ->call('saveRole', $staff->id)
        ->call('startEditPermissions', $staff->id)
        ->set('editPermissionsValues', ['donations.view'])
        ->call('savePermissions', $staff->id);

    $role = auditEntry('user.role_changed');
    expect($role->user_id)->toBe($owner->id)
        ->and($role->subject_id)->toBe($staff->id)
        ->and($role->properties['before']['role'])->toBe('foundation_staff')
        ->and($role->properties['after']['role'])->toBe('media_manager')
        ->and($role->properties['actor_name'])->toBe($owner->name);

    expect(auditEntry('user.permissions_changed')->properties['added'])->toBe(['donations.view']);
});

it('records account lifecycle actions without any password or 2FA secret', function () {
    $owner = User::factory()->owner()->create();
    $staff = User::factory()->foundationStaff()->withTwoFactor()->create();
    $this->actingAs($owner);

    $component = Livewire::test('admin.users')
        ->set('name', 'New Person')->set('email', 'new@example.org')->set('role', 'foundation_staff')
        ->call('invite')
        ->call('resetPassword', $staff->id)
        ->call('startResetPassword', $staff->id)
        ->set('newPassword', 'Chosen-Passw0rd!x')->set('newPasswordConfirm', 'Chosen-Passw0rd!x')
        ->call('saveResetPassword')
        ->call('toggleRequireTwoFactor', $staff->id)
        ->call('disableTwoFactor', $staff->id)
        ->call('toggleActive', $staff->id)
        ->call('delete', $staff->id);

    $generated = $component->get('generatedPassword');

    foreach (['user.created', 'user.two_factor_required', 'user.two_factor_disabled', 'user.deactivated', 'user.deleted'] as $action) {
        expect(auditEntry($action)->user_id)->toBe($owner->id);
    }
    expect(AuditLog::query()->where('action', 'user.password_reset')->count())->toBe(2);

    $logged = auditJson();
    expect($logged)->not->toContain('Chosen-Passw0rd!x')
        ->and($logged)->not->toContain((string) $generated)
        ->and($logged)->not->toContain('$2y$')
        ->and($logged)->not->toContain('secret')
        ->and($logged)->not->toContain('recovery');
});

it('records a password changed by its owner', function () {
    $user = User::factory()->foundationStaff()->create(['password' => Hash::make('password')]);
    $this->actingAs($user);

    Livewire::test('pages::settings.password')
        ->set('current_password', 'password')
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(auditEntry('user.password_changed')->user_id)->toBe($user->id)
        ->and(auditJson())->not->toContain('brand-new-password');
});

it('records donations created, updated and deleted', function () {
    $staff = User::factory()->foundationStaff()->create();
    $this->actingAs($staff);

    Livewire::test('admin.donations')
        ->call('startCreate')
        ->set('donor_name', 'Ama Mensah')->set('amount', '50')
        ->call('save')->assertHasNoErrors();

    $donation = Donation::query()->sole();

    Livewire::test('admin.donations')
        ->call('startEdit', $donation->id)
        ->set('amount', '75')
        ->call('save')
        ->call('delete', $donation->id);

    expect(auditEntry('donation.created')->user_id)->toBe($staff->id)
        ->and(auditEntry('donation.updated')->properties['before']['amount_cents'])->toBe(5000)
        ->and(auditEntry('donation.updated')->properties['after']['amount_cents'])->toBe(7500)
        ->and(auditEntry('donation.deleted')->subject_id)->toBe($donation->id);
});

it('records which beneficiary fields changed but not the case notes themselves', function () {
    $staff = User::factory()->foundationStaff()->create();
    $beneficiary = Beneficiary::factory()->create(['notes' => 'Old private note']);
    $this->actingAs($staff);

    Livewire::test('admin.beneficiaries')
        ->call('startEdit', $beneficiary->id)
        ->set('notes', 'Confidential medical history detail')
        ->set('status', 'approved')
        ->call('save')->assertHasNoErrors()
        ->call('delete', $beneficiary->id);

    $entry = auditEntry('beneficiary.updated');
    expect($entry->user_id)->toBe($staff->id)
        ->and($entry->properties['fields'])->toContain('notes', 'status')
        ->and($entry->properties['after'])->toBe(['status' => 'approved'])
        ->and(auditJson())->not->toContain('Confidential medical history')
        ->and(auditJson())->not->toContain('Old private note')
        ->and(auditEntry('beneficiary.archived')->subject_id)->toBe($beneficiary->id);
});

it('records guardian consent and publishing of a sponsorship profile', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin);
    $child = Beneficiary::factory()->active()->create(['date_of_birth' => today()->subYears(8)]);

    $form = Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->set('publicDisplayName', 'Kofi')->set('publicCountry', 'Ghana')
        ->set('publicStory', 'Kofi enjoys learning and playing football.')
        ->set('guardianConsent', true)->set('guardianConsentReference', 'Consent document 123')
        ->set('sponsorshipPublished', true)
        ->set('sponsorshipPhoto', UploadedFile::fake()->image('approved.jpg'))
        ->call('saveSponsorship')->assertHasNoErrors();

    $form->set('guardianConsent', false)->call('saveSponsorship')->assertHasNoErrors();

    foreach (['beneficiary.consent_given', 'beneficiary.sponsorship_published', 'beneficiary.consent_withdrawn', 'beneficiary.sponsorship_unpublished'] as $action) {
        expect(auditEntry($action))->user_id->toBe($admin->id)->subject_id->toBe($child->id);
    }
    expect(auditJson())->not->toContain('enjoys learning');
});

it('records document uploads, views and deletions without the file contents', function () {
    Storage::fake('beneficiaries');
    $staff = User::factory()->foundationStaff()->create();
    $this->actingAs($staff);
    $beneficiary = Beneficiary::factory()->create();

    $page = Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->set('documentUploads', [UploadedFile::fake()->createWithContent('medical.txt', 'PRIVATE MEDICAL NOTE')])
        ->call('uploadDocuments')->assertHasNoErrors();

    $document = BeneficiaryDocument::query()->sole();
    $this->get(DocumentDownloadController::beneficiaryDocumentUrl($document, true))->assertOk();
    $this->get(DocumentDownloadController::beneficiaryDocumentUrl($document))->assertOk();
    $page->call('deleteDocument', $document->id);

    expect(auditEntry('beneficiary_document.uploaded')->user_id)->toBe($staff->id)
        ->and(auditEntry('beneficiary_document.downloaded')->properties['file_name'])->toBe('medical.txt')
        ->and(auditEntry('beneficiary_document.viewed')->subject_id)->toBe($document->id)
        ->and(auditEntry('beneficiary_document.deleted')->user_id)->toBe($staff->id)
        ->and(auditJson())->not->toContain('PRIVATE MEDICAL NOTE');
});

it('records application decisions', function () {
    $staff = User::factory()->foundationStaff()->create();
    $this->actingAs($staff);
    $rejected = BeneficiaryApplication::factory()->create();
    $approved = BeneficiaryApplication::factory()->create();

    Livewire::test('admin.beneficiary-applications')
        ->call('setStatus', $rejected->id, BeneficiaryApplicationStatus::Rejected->value)
        ->call('convertToBeneficiary', $approved->id);

    expect(auditEntry('beneficiary_application.rejected')->subject_id)->toBe($rejected->id)
        ->and(auditEntry('beneficiary_application.approved')->subject_id)->toBe($approved->id)
        ->and(auditEntry('beneficiary.created')->user_id)->toBe($staff->id);
});

it('records sponsorships being activated and ended', function () {
    $staff = User::factory()->foundationStaff()->create();
    $this->actingAs($staff);
    $sponsorship = Sponsorship::factory()->create();

    Livewire::test('admin.sponsorships')
        ->call('startActivate', $sponsorship->id)
        ->call('activate')->assertHasNoErrors()
        ->call('end', $sponsorship->id);

    expect($sponsorship->refresh()->status)->toBe(SponsorshipStatus::Ended)
        ->and(auditEntry('sponsorship.activated')->user_id)->toBe($staff->id)
        ->and(auditEntry('sponsorship.ended')->subject_id)->toBe($sponsorship->id);
});

it('records site setting changes but never the Instagram token', function () {
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin);

    Setting::write('site_settings', ['phone' => '+233 1']);
    Setting::write('site_settings', ['phone' => '+233 2']);
    InstagramToken::store('IGQ-very-secret-token');

    $entries = AuditLog::query()->where('action', 'settings.site_settings_updated')->get();
    expect($entries)->toHaveCount(2)
        ->and($entries->last()->properties['before'])->toBe(['phone' => '+233 1'])
        ->and($entries->last()->properties['after'])->toBe(['phone' => '+233 2'])
        ->and(auditJson())->not->toContain('IGQ-very-secret-token')
        ->and(AuditLog::query()->count())->toBe(2);
});

it('records a newsletter being sent', function () {
    $staff = User::factory()->foundationStaff()->create();
    $this->actingAs($staff);
    NewsletterSubscriber::factory()->create();
    $campaign = NewsletterCampaign::factory()->create();

    Livewire::test('admin.newsletter-campaigns')->call('askSend', $campaign->id)->call('send');

    expect(auditEntry('newsletter.campaign_sent'))->user_id->toBe($staff->id)->subject_id->toBe($campaign->id);
});

it('records demo:remove as a system action', function () {
    $this->artisan('demo:remove', ['--force' => true])->assertSuccessful();

    expect(auditEntry('system.demo_removed')->user_id)->toBeNull();
});

it('never stores secret-looking keys passed in properties', function () {
    Audit::record('test.action', 'Testing', properties: ['password' => 'x', 'nested' => ['api_token' => 'y', 'ok' => 'z']]);

    expect(AuditLog::query()->sole()->properties)->toBe(['nested' => ['ok' => 'z']]);
});

it('lets the action finish when the audit log cannot be written', function () {
    Exceptions::fake();
    Schema::drop('audit_logs');
    $this->actingAs(User::factory()->foundationStaff()->create());

    Livewire::test('admin.donations')
        ->call('startCreate')
        ->set('donor_name', 'Kwame Asante')->set('amount', '20')
        ->call('save')->assertHasNoErrors();

    expect(Donation::query()->where('donor_name', 'Kwame Asante')->exists())->toBeTrue();
    Exceptions::assertReported(QueryException::class);
});

it('refuses to change or delete an entry', function () {
    $entry = Audit::record('test.action', 'Original');

    expect(fn () => $entry->update(['description' => 'Rewritten']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class)
        ->and($entry->fresh()->description)->toBe('Original');
});

it('is only open to Super Admin and Admin', function (UserRole $role, bool $allowed) {
    $user = User::factory()->withRole($role)->create(['permissions' => ['audit_log.view']]);

    $this->actingAs($user)->get(route('admin.audit-log.index'))->assertStatus($allowed ? 200 : 403);

    $component = Livewire::actingAs($user)->test('admin.audit-log');
    $allowed ? $component->assertOk() : $component->assertForbidden();
})->with([
    'owner' => [UserRole::Owner, true],
    'admin' => [UserRole::SuperAdmin, true],
    'foundation staff' => [UserRole::FoundationStaff, false],
    'volunteer coordinator' => [UserRole::VolunteerCoordinator, false],
    'media manager' => [UserRole::MediaManager, false],
    'volunteer' => [UserRole::Volunteer, false],
]);

it('filters by person, action, date range and description', function () {
    $owner = User::factory()->owner()->create(['name' => 'Olivia Owner']);
    $staff = User::factory()->foundationStaff()->create(['name' => 'Sam Staff']);

    $this->travelTo(now()->subDays(10));
    Audit::record('donation.created', 'Old gift from Ama', actor: $staff);
    $this->travelBack();
    Audit::record('donation.deleted', 'Deleted gift from Kojo', actor: $staff);
    Audit::record('user.role_changed', 'Changed role for Esi', actor: $owner);

    $this->actingAs($owner);
    $page = fn () => Livewire::test('admin.audit-log');

    $page()->assertSee('Old gift from Ama')->assertSee('Deleted gift from Kojo')->assertSee('Changed role for Esi');

    $page()->set('userFilter', (string) $owner->id)
        ->assertSee('Changed role for Esi')->assertDontSee('Deleted gift from Kojo');

    $page()->set('actionFilter', 'donation.*')
        ->assertSee('Old gift from Ama')->assertSee('Deleted gift from Kojo')->assertDontSee('Changed role for Esi');

    $page()->set('actionFilter', 'donation.deleted')
        ->assertSee('Deleted gift from Kojo')->assertDontSee('Old gift from Ama');

    $page()->set('fromDate', today()->subDay()->toDateString())->set('toDate', today()->toDateString())
        ->assertDontSee('Old gift from Ama')->assertSee('Deleted gift from Kojo');

    $page()->set('search', 'Kojo')
        ->assertSee('Deleted gift from Kojo')->assertDontSee('Changed role for Esi')->assertDontSee('Old gift from Ama');
});

it('exports the filtered log as CSV and records the export', function () {
    $owner = User::factory()->owner()->create();
    Audit::record('donation.deleted', 'Deleted gift from Kojo', actor: $owner);
    Audit::record('user.role_changed', '=HYPERLINK("x") role change', actor: $owner);
    $this->actingAs($owner);

    $response = Livewire::test('admin.audit-log')
        ->set('actionFilter', 'user.role_changed')
        ->call('exportCsv');

    $response->assertFileDownloaded();
    expect(auditEntry('audit_log.exported'))->user_id->toBe($owner->id)
        ->properties->toMatchArray(['rows' => 1]);
});

it('offers no way to edit or delete entries', function () {
    $owner = User::factory()->owner()->create();
    Audit::record('donation.deleted', 'Deleted gift from Kojo', actor: $owner);

    $this->actingAs($owner)->get(route('admin.audit-log.index'))
        ->assertOk()
        ->assertSee('Deleted gift from Kojo')
        ->assertDontSee('wire:click="delete', false)
        ->assertDontSee('wire:click="edit', false);
});
