<?php

use App\Enums\UserRole;
use App\Models\Faq;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Faq::query()->delete();
    $this->actingAs(User::factory()->superAdmin()->create());
});

it('lists FAQs on the admin page', function () {
    Faq::factory()->create(['question' => 'Where do donations go?']);

    $this->get(route('admin.faqs.index'))->assertOk()->assertSee('Where do donations go?');
});

it('creates a question for a page', function () {
    Livewire::test('admin.faqs')
        ->call('startCreate')
        ->set('placement', 'sponsorship')
        ->set('question', 'Can I visit?')
        ->set('answer', 'Visits are arranged by the team.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    expect(Faq::query()->sole())
        ->page->toBe('sponsorship')
        ->question->toBe('Can I visit?')
        ->is_published->toBeTrue();
});

it('validates the question, answer and page', function () {
    Livewire::test('admin.faqs')
        ->call('startCreate')
        ->set('placement', 'nowhere')
        ->set('question', '')
        ->set('answer', '')
        ->call('save')
        ->assertHasErrors(['placement', 'question', 'answer']);
});

it('edits, hides and deletes a question', function () {
    $faq = Faq::factory()->create(['question' => 'Old?']);

    Livewire::test('admin.faqs')
        ->call('startEdit', $faq->id)
        ->set('question', 'New?')
        ->call('save')
        ->assertHasNoErrors()
        ->call('togglePublished', $faq->id);

    expect($faq->fresh())->question->toBe('New?')->is_published->toBeFalse();

    Livewire::test('admin.faqs')->call('delete', $faq->id);

    expect(Faq::query()->count())->toBe(0);
});

it('moves a question up within its page', function () {
    $first = Faq::factory()->create(['page' => 'contact', 'sort' => 0]);
    $second = Faq::factory()->create(['page' => 'contact', 'sort' => 1]);

    Livewire::test('admin.faqs')->call('move', $second->id, -1);

    expect(Faq::query()->onPage('contact')->ordered()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

it('blocks roles without FAQ access', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::VolunteerCoordinator->value]));

    $this->get(route('admin.faqs.index'))->assertForbidden();
});

it('shows only published FAQs for each public page', function () {
    Faq::factory()->create(['page' => 'contact', 'question' => 'Contact question?']);
    Faq::factory()->hidden()->create(['page' => 'contact', 'question' => 'Hidden question?']);
    Faq::factory()->create(['page' => 'sponsorship', 'question' => 'Sponsor question?']);

    $this->get(route('contact'))->assertOk()->assertSee('Contact question?')->assertDontSee('Hidden question?')->assertDontSee('Sponsor question?');
    $this->get(route('sponsor-a-child'))->assertOk()->assertSee('Sponsor question?')->assertDontSee('Contact question?');
});
