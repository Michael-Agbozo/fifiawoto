<?php

use App\Enums\ContactMessageStatus;
use App\Enums\TimelineEntryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryFolder;
use App\Models\ContactMessage;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('opens and closes a beneficiary application', function () {
    $application = BeneficiaryApplication::factory()->create();

    Livewire::test('admin.beneficiary-applications')
        ->call('viewApplication', $application->id)
        ->assertSet('viewingId', $application->id)
        ->assertSee($application->full_name)
        ->call('closeView')
        ->assertSet('viewingId', null);
});

it('renames a beneficiary folder and keeps it scoped to that beneficiary', function () {
    $beneficiary = Beneficiary::factory()->create();
    $folder = BeneficiaryFolder::query()->create(['beneficiary_id' => $beneficiary->id, 'name' => 'School reports', 'slug' => 'school-reports']);
    $otherFolder = BeneficiaryFolder::query()->create(['beneficiary_id' => Beneficiary::factory()->create()->id, 'name' => 'Untouched', 'slug' => 'untouched']);

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->call('startRenameFolder', $folder->id)
        ->assertSet('renameFolderName', 'School reports')
        ->set('renameFolderName', 'Term reports')
        ->call('saveRename', $folder->id)
        ->assertHasNoErrors()
        ->assertSet('renameFolderId', null)
        ->set('renameFolderName', 'Hijacked')
        ->call('saveRename', $otherFolder->id);

    expect($folder->fresh()->name)->toBe('Term reports')
        ->and($otherFolder->fresh()->name)->toBe('Untouched');
});

it('requires a folder name when renaming', function () {
    $beneficiary = Beneficiary::factory()->create();
    $folder = BeneficiaryFolder::query()->create(['beneficiary_id' => $beneficiary->id, 'name' => 'Medical', 'slug' => 'medical']);

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->call('startRenameFolder', $folder->id)
        ->set('renameFolderName', '')
        ->call('saveRename', $folder->id)
        ->assertHasErrors(['renameFolderName' => 'required']);

    expect($folder->fresh()->name)->toBe('Medical');
});

it('adds a timeline note to a beneficiary', function () {
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $beneficiary])
        ->call('startTimelineEntry')
        ->assertSet('showTimelineForm', true)
        ->set('timelineType', TimelineEntryType::Note->value)
        ->set('timelineDescription', 'Visited the family and delivered school supplies.')
        ->call('saveTimelineEntry')
        ->assertHasNoErrors()
        ->assertSet('showTimelineForm', false);

    $entry = $beneficiary->timeline()->sole();
    expect($entry->description)->toBe('Visited the family and delivered school supplies.')
        ->and($entry->recorded_by)->toBe(auth()->id());
});

it('moves an inbox message back to new and deletes it', function () {
    $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::Resolved->value]);

    Livewire::test('admin.inbox')
        ->call('markNew', $message->id)
        ->assertSet('flashMessage', 'Message moved back to New.');

    expect($message->fresh()->status)->toBe(ContactMessageStatus::New);

    Livewire::test('admin.inbox')
        ->call('openMessage', $message->id)
        ->call('askDelete', $message->id)
        ->call('delete', $message->id)
        ->assertSet('openMessageId', null)
        ->assertSet('flashMessage', 'Message deleted.');

    expect(ContactMessage::query()->find($message->id))->toBeNull();
});

it('blocks inbox deletes for roles without delete permission', function () {
    $this->actingAs(User::factory()->mediaManager()->create());
    $message = ContactMessage::factory()->create();

    Livewire::test('admin.inbox')
        ->call('delete', $message->id)
        ->assertHasErrors('delete');

    expect(ContactMessage::query()->find($message->id))->not->toBeNull();
})->skip(fn () => User::factory()->mediaManager()->make()->canDo('inbox', 'delete'), 'Media Manager may delete inbox messages in this configuration.');

it('opens volunteer applications and volunteer records', function () {
    $application = VolunteerApplication::factory()->create();
    $volunteer = Volunteer::factory()->create();

    Livewire::test('admin.volunteers')
        ->call('viewApplication', $application->id)
        ->assertSet('viewingApplicationId', $application->id)
        ->assertSee($application->full_name)
        ->call('closeView')
        ->assertSet('viewingApplicationId', null)
        ->call('viewVolunteer', $volunteer->id)
        ->assertSet('viewingVolunteerId', $volunteer->id)
        ->assertSee($volunteer->full_name)
        ->call('closeView')
        ->assertSet('viewingVolunteerId', null);
});
