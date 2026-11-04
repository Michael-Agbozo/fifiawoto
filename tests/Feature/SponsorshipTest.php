<?php

use App\Enums\UserRole;
use App\Mail\ContactFormReceived;
use App\Models\Beneficiary;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

function publicChild(array $attributes = []): Beneficiary
{
    return Beneficiary::factory()->active()->create(array_merge([
        'date_of_birth' => today()->subYears(9),
        'public_display_name' => 'Ama', 'public_story' => 'Ama enjoys reading and learning new things.',
        'public_country' => 'Ghana', 'public_photo_path' => 'sponsorship/approved.jpg',
        'guardian_consent_at' => now(), 'guardian_consent_reference' => 'Signed consent record',
        'sponsorship_published' => true,
    ], $attributes));
}

it('shows only approved public fields and supports filters', function () {
    $child = publicChild(['full_name' => 'PRIVATE FULL NAME', 'description' => 'PRIVATE CASE NOTES', 'gender' => 'female']);
    $this->get(route('sponsor-a-child'))->assertOk()->assertSee('Ama')->assertDontSee('PRIVATE FULL NAME')->assertDontSee('PRIVATE CASE NOTES');
    $this->get(route('sponsor-a-child', ['gender' => 'male']))->assertOk()->assertDontSee('Meet Ama');
    $this->get(route('sponsor-a-child', ['age' => '12-17']))->assertOk()->assertDontSee('Meet Ama');
    $this->get(route('sponsor-a-child', ['country' => 'Ghana']))->assertOk()->assertSee('Meet Ama');
    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))->assertOk()->assertDontSee('PRIVATE FULL NAME')->assertDontSee('PRIVATE CASE NOTES');
});

it('hides revoked inactive adult and incomplete profiles', function (array $attributes) {
    $child = publicChild($attributes);
    $this->get(route('sponsor-a-child'))->assertOk()->assertDontSee('Meet Ama');
    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))->assertNotFound();
})->with([
    [['guardian_consent_at' => null]], [['sponsorship_published' => false]],
    [['status' => 'completed']], [['date_of_birth' => today()->subYears(18)]],
    [['public_photo_path' => null]], [['public_story' => '']],
]);

it('captures a verified sponsorship enquiry once and queues notification', function () {
    Mail::fake();
    $child = publicChild();
    $form = Livewire::test('site.contact-form', ['sponsorshipProfile' => $child->public_sponsorship_id])
        ->set('full_name', 'A Donor')->set('email', 'donor@example.test')
        ->set('message', 'I would like to discuss supporting this child.')->set('consent', true);
    $this->travel(5)->seconds();
    $form->call('submit')->assertHasNoErrors()->assertSet('submitted', true)->call('submit');
    expect(ContactMessage::query()->count())->toBe(1);
    expect(ContactMessage::query()->sole()->message)->toContain($child->public_sponsorship_id);
    Mail::assertQueued(ContactFormReceived::class, 1);
});

it('rechecks consent on enquiry submission', function () {
    $child = publicChild();
    $form = Livewire::test('site.contact-form', ['sponsorshipProfile' => $child->public_sponsorship_id]);
    $child->update(['guardian_consent_at' => null]);
    $form->call('submit')->assertNotFound();
    expect(ContactMessage::query()->count())->toBe(0);
});

it('requires staff update permission for publishing', function () {
    $this->actingAs(User::factory()->create());
    $child = publicChild();
    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])->assertForbidden();
});

it('publishes only with approved public content and removes photo when consent is revoked', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());
    $child = Beneficiary::factory()->active()->create(['date_of_birth' => today()->subYears(8)]);
    $form = Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->set('publicDisplayName', 'Kofi')->set('publicCountry', 'Ghana')
        ->set('publicStory', 'Kofi enjoys learning and playing football.')
        ->set('guardianConsent', true)->set('guardianConsentReference', 'Consent document 123')
        ->set('sponsorshipPublished', true);
    $form->call('saveSponsorship')->assertHasErrors('sponsorshipPhoto');
    $form->set('sponsorshipPhoto', UploadedFile::fake()->image('approved.jpg'))
        ->call('saveSponsorship')->assertHasNoErrors();
    $photo = $child->refresh()->public_photo_path;
    expect($child->sponsorship_published)->toBeTrue();
    Storage::disk('public')->assertExists($photo);
    $form->set('guardianConsent', false)->call('saveSponsorship')->assertHasNoErrors();
    expect($child->refresh()->sponsorship_published)->toBeFalse();
    Storage::disk('public')->assertMissing($photo);
    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))->assertNotFound();
});

it('rejects tampering with the enquiry profile', function () {
    $child = publicChild();
    Livewire::test('site.contact-form', ['sponsorshipProfile' => $child->public_sponsorship_id])
        ->set('sponsorshipProfile', (string) Str::uuid());
})->throws(CannotUpdateLockedPropertyException::class);

it('does not expose unsafe photo paths or sequential identifiers', function () {
    $child = publicChild(['public_photo_path' => 'sponsorship/../private.jpg']);
    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))->assertNotFound();
    $this->get('/sponsor-a-child/'.$child->id)->assertNotFound();
});

it('blocks view-only staff and permission revocation during editing', function () {
    $user = User::factory()->create(['role' => UserRole::MediaManager, 'permissions' => ['beneficiaries.view']]);
    $this->actingAs($user);
    $child = publicChild();
    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])->call('saveSponsorship')->assertForbidden();
    $user->forceFill(['permissions' => ['beneficiaries.view', 'beneficiaries.update']])->save();
    $form = Livewire::test('admin.beneficiary-show', ['beneficiary' => $child]);
    $user->forceFill(['permissions' => ['beneficiaries.view']])->save();
    $form->call('saveSponsorship')->assertForbidden();
});

it('enforces exact birthday and age bracket boundaries and retains paginated filters', function () {
    $adult = publicChild(['date_of_birth' => today()->subYears(18)]);
    $this->get(route('sponsor-a-child.show', $adult->public_sponsorship_slug))->assertNotFound();
    $young = publicChild(['date_of_birth' => today()->subYears(6), 'gender' => 'female']);
    $this->get(route('sponsor-a-child', ['age' => '0-5']))->assertDontSee('Meet Ama');
    for ($i = 0; $i < 10; $i++) {
        publicChild(['public_display_name' => 'Child '.$i, 'gender' => 'female', 'date_of_birth' => today()->subYears(6)]);
    }
    $response = $this->get(route('sponsor-a-child', ['age' => '6-11', 'gender' => 'female', 'country' => 'Ghana']))
        ->assertOk()->assertSee('Meet Ama');

    $children = $response->viewData('children');

    expect($children->lastPage())->toBe(2)
        ->and($children->nextPageUrl())->toContain('page=2', 'age=6-11', 'gender=female', 'country=Ghana');
});

it('shows the selected public child and sponsorship subject in the enquiry form', function () {
    $child = publicChild();
    Livewire::test('site.contact-form', ['sponsorshipProfile' => $child->public_sponsorship_id])
        ->assertSee('Enquire about supporting Ama')
        ->assertSet('subject', 'sponsorship')
        ->assertSee('value="sponsorship" selected', false)
        ->assertSee('Send sponsorship enquiry');
});

it('saves a long approved story and renders every escaped paragraph before support actions', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $child = publicChild(['full_name' => 'PRIVATE FULL NAME', 'description' => 'PRIVATE CASE NOTES']);
    $firstParagraph = 'A childhood full of curiosity. '.str_repeat('Reading, learning and time with friends. ', 65);
    $secondParagraph = 'A second chapter & a new interest.\nA line within the same paragraph.';
    $lastParagraph = '<script>alert("unsafe")</script> The complete final chapter.';
    $story = $firstParagraph."\r\n\r\n".str_replace('\\n', "\n", $secondParagraph)."\n\n".$lastParagraph;

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->set('publicStory', $story)
        ->call('saveSponsorship')
        ->assertHasNoErrors();

    expect($child->refresh()->public_story)->toBe($story);

    $response = $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))
        ->assertOk()
        ->assertSeeInOrder([$firstParagraph, 'A second chapter & a new interest.', 'A line within the same paragraph.', $lastParagraph, 'Sponsor Ama'])
        ->assertSee('href="'.route('sponsor-a-child.checkout', $child->public_sponsorship_slug).'"', false)
        ->assertDontSee('Make a donation')
        ->assertDontSee('<script>alert("unsafe")</script>', false)
        ->assertDontSee('PRIVATE FULL NAME')
        ->assertDontSee('PRIVATE CASE NOTES');

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $paragraphs = $xpath->query('//div[@role="tabpanel"][1]//p');

    expect($paragraphs)->toHaveCount(3)
        ->and($paragraphs->item(1)->textContent)->toContain("interest.\nA line")
        ->and($paragraphs->item(2)->textContent)->toBe($lastParagraph);
});

it('retains selected filter values and links every card to its public profile', function () {
    $child = publicChild(['gender' => 'female', 'public_display_name' => 'Ama (Demo)']);

    $response = $this->get(route('sponsor-a-child', ['gender' => 'female', 'age' => '6-11', 'country' => 'Ghana']))
        ->assertOk()->assertSee('Meet Ama (Demo)')
        ->assertDontSee('Illustrative foundation photograph')
        ->assertSee('href="'.route('sponsor-a-child.show', $child->public_sponsorship_slug).'"', false);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    foreach (['gender' => 'female', 'age' => '6-11', 'country' => 'Ghana'] as $filter => $value) {
        $option = $xpath->query('//select[@name="'.$filter.'"]/option[@selected]')->item(0);
        expect($option->getAttribute('value') ?: $option->textContent)->toBe($value);
        expect($xpath->query('//label[@for="sponsor-'.$filter.'"]'))->toHaveCount(1);
    }
});

it('marks demo profiles as previews without labelling real profiles as demos or captioning photos', function () {
    $demo = publicChild(['public_display_name' => 'Ama (Demo)']);
    $real = publicChild();

    $this->get(route('sponsor-a-child.show', $demo->public_sponsorship_slug))
        ->assertOk()->assertSee('Meet Ama (Demo)')->assertDontSee('Illustrative foundation photograph')
        ->assertSee('An illustrative preview of a longer child profile.')
        ->assertDontSee('An introduction shared with guardian consent.');
    $this->get(route('sponsor-a-child.show', $real->public_sponsorship_slug))
        ->assertOk()->assertDontSee('Illustrative foundation photograph')
        ->assertSee('An introduction shared with guardian consent.')
        ->assertDontSee('An illustrative preview of a longer child profile.');
});

it('marks sponsor a child as the current nav item on child pages', function () {
    $child = publicChild();
    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))
        ->assertOk()
        ->assertSee('class="whitespace-nowrap text-sm font-semibold text-gold-500" wire:navigate>Sponsor a Child</a>', false);
});

it('shows a breadcrumb back through Sponsor a Child on each profile', function () {
    $child = publicChild();

    $this->get(route('sponsor-a-child.show', $child->public_sponsorship_slug))
        ->assertOk()
        ->assertSee('aria-label="Breadcrumb"', escape: false)
        ->assertSeeInOrder([route('home'), route('sponsor-a-child'), 'aria-current="page">Ama</span>'], escape: false)
        ->assertDontSee('All children</a>', escape: false);
});

it('deletes gallery photos from public storage when guardian consent is withdrawn', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());
    $child = Beneficiary::factory()->create([
        'public_display_name' => 'Ama',
        'public_country' => 'Ghana',
        'guardian_consent_at' => now(),
        'guardian_consent_reference' => 'Signed form',
    ]);
    Storage::disk('public')->put('sponsorship/gallery/ama.jpg', 'photo');
    $photo = $child->sponsorshipPhotos()->create(['path' => 'sponsorship/gallery/ama.jpg', 'sort' => 1]);

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->set('guardianConsent', false)
        ->call('saveSponsorship')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing('sponsorship/gallery/ama.jpg');
    expect($photo->fresh())->toBeNull();
});

it('keeps gallery photos when sponsorship settings are saved with consent in place', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());
    $child = Beneficiary::factory()->create([
        'public_display_name' => 'Ama',
        'public_country' => 'Ghana',
        'guardian_consent_at' => now(),
        'guardian_consent_reference' => 'Signed form',
    ]);
    Storage::disk('public')->put('sponsorship/gallery/ama.jpg', 'photo');
    $child->sponsorshipPhotos()->create(['path' => 'sponsorship/gallery/ama.jpg', 'sort' => 1]);

    Livewire::test('admin.beneficiary-show', ['beneficiary' => $child])
        ->call('saveSponsorship')
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists('sponsorship/gallery/ama.jpg');
});
