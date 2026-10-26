<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Support\SitePopup;
use Livewire\Livewire;

function savePopup(array $overrides = []): void
{
    SitePopup::save(array_merge(SitePopup::defaults(), ['enabled' => true], $overrides));
}

it('does not render while disabled', function () {
    savePopup(['enabled' => false]);

    $this->get(route('home'))->assertOk()->assertDontSee('data-site-popup', false);
});

it('renders the configured type with its trigger settings', function () {
    savePopup(['type' => 'donate', 'trigger' => 'scroll', 'trigger_scroll_percent' => 60, 'frequency' => 'once']);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('data-site-popup', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-labelledby="site-popup-heading"', false)
        ->assertSee('Coordinate a gift')
        ->assertSee(route('contact', ['subject' => 'donation']), false);
});

it('only shows on the targeted page', function () {
    savePopup(['type' => 'volunteer', 'pages' => 'events']);

    $this->get(route('home'))->assertDontSee('data-site-popup', false);
    $this->get(route('events.index'))->assertSee('data-site-popup', false);
});

it('embeds a compact newsletter form', function () {
    savePopup(['type' => 'newsletter']);

    $this->get(route('about'))
        ->assertSee('Stay connected')
        ->assertSeeLivewire('site.newsletter-signup');
});

it('promotes an upcoming event and hides once it has passed', function () {
    $event = Event::factory()->create([
        'title' => 'Back to School Drive',
        'starts_at' => now()->addWeek(),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);
    savePopup(['type' => 'event', 'event_id' => $event->id]);

    $this->get(route('home'))->assertSee('Back to School Drive')->assertSee(route('events.show', $event), false);

    $event->update(['starts_at' => now()->subWeek()]);
    $this->get(route('home'))->assertDontSee('data-site-popup', false);
});

it('escapes announcement content', function () {
    savePopup([
        'type' => 'announcement',
        'announcement_heading' => 'Gala <script>alert(1)</script>',
        'announcement_body' => 'Join us.',
        'announcement_cta_label' => 'Book now',
        'announcement_cta_url' => '/events',
    ]);

    $this->get(route('home'))
        ->assertSee('Gala &lt;script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Book now');
});

it('never renders on admin pages', function () {
    savePopup();
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-site-popup', false);
});

it('changes the dismissal key whenever the popup is edited', function () {
    $before = SitePopup::dismissalKey(array_merge(SitePopup::defaults(), ['type' => 'donate']));
    $after = SitePopup::dismissalKey(array_merge(SitePopup::defaults(), ['type' => 'volunteer']));

    expect($before)->not->toBe($after);
});

it('lets an admin configure the popup', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(route('admin.settings.popup'))->assertOk()->assertSeeLivewire('admin.popup-settings');

    Livewire::test('admin.popup-settings')
        ->set('enabled', true)
        ->set('type', 'announcement')
        ->set('announcement_heading', 'Annual gala')
        ->set('announcement_cta_label', 'Details')
        ->set('announcement_cta_url', 'https://fifiawoto.org/gala')
        ->set('trigger', 'exit_intent')
        ->set('frequency', 'always')
        ->set('pages', 'home')
        ->call('save')
        ->assertHasNoErrors();

    expect(SitePopup::config())
        ->enabled->toBeTrue()
        ->type->toBe('announcement')
        ->announcement_heading->toBe('Annual gala')
        ->trigger->toBe('exit_intent')
        ->pages->toBe('home');
});

it('validates the popup configuration', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.popup-settings')
        ->set('type', 'event')
        ->call('save')
        ->assertHasErrors(['event_id' => 'required_if']);

    Livewire::test('admin.popup-settings')
        ->set('type', 'announcement')
        ->set('announcement_heading', 'Hi')
        ->set('announcement_cta_label', 'Click')
        ->set('announcement_cta_url', 'javascript:alert(1)')
        ->call('save')
        ->assertHasErrors(['announcement_cta_url' => 'regex']);

    Livewire::test('admin.popup-settings')
        ->set('type', 'not-a-type')
        ->call('save')
        ->assertHasErrors('type');
});

it('restricts popup settings to permitted admins', function () {
    $this->actingAs(User::factory()->foundationStaff()->create());

    $this->get(route('admin.settings.popup'))->assertForbidden();
    Livewire::test('admin.popup-settings')->assertForbidden();
});
