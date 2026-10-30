<?php

use App\Enums\EventStatus;
use App\Enums\MediaCategory;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\MediaItem;
use App\Models\User;
use App\Support\MediaAlbums;
use App\Support\MediaLink;
use Database\Seeders\DemoMediaSeeder;
use Database\Seeders\EventSeeder;
use Livewire\Livewire;

function publishedEvent(array $attributes = []): Event
{
    return Event::factory()->create(array_merge([
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ], $attributes));
}

it('lists each event as one album card, newest first, with year options', function () {
    $current = publishedEvent(['title' => 'Back-to-School Drive', 'location' => 'Lomé', 'country' => 'Togo', 'starts_at' => '2026-09-14 09:00']);
    $last = publishedEvent(['title' => 'Christmas Food Drive', 'starts_at' => '2025-12-20 09:00']);
    EventImage::factory()->count(2)->create(['event_id' => $current->id]);
    EventImage::factory()->create(['event_id' => $last->id]);

    $this->get(route('media'))
        ->assertOk()
        ->assertSee('<option value="2026">2026</option>', escape: false)
        ->assertSee('<option value="2025">2025</option>', escape: false)
        ->assertSeeInOrder(['Back-to-School Drive', 'Christmas Food Drive'])
        ->assertSee('Lomé, Togo')
        ->assertSee(route('media.event', $current), escape: false);
});

it('gives each event its own gallery page with media and event photos', function () {
    $event = publishedEvent(['title' => 'Back-to-School Drive', 'starts_at' => '2026-09-14 09:00']);
    MediaItem::factory()->create(['event_id' => $event->id, 'caption' => 'Packing school kits', 'category' => MediaCategory::EducationPrograms->value]);
    EventImage::factory()->create(['event_id' => $event->id, 'caption' => 'Children trying on uniforms']);

    $this->get(route('media.event', $event))
        ->assertOk()
        ->assertSee('Back-to-School Drive')
        ->assertSee('14 September 2026')
        ->assertSee('Packing school kits')
        ->assertSee('Children trying on uniforms')
        ->assertSee('2 items')
        ->assertSee(route('events.show', $event), escape: false);
});

it('hides galleries for draft events and events without photos', function () {
    $draft = Event::factory()->draft()->create();
    EventImage::factory()->create(['event_id' => $draft->id]);
    $empty = publishedEvent();

    $this->get(route('media.event', $draft))->assertNotFound();
    $this->get(route('media.event', $empty))->assertNotFound();
});

it('collects media without a public event into programme galleries', function () {
    MediaItem::factory()->create(['category' => MediaCategory::Volunteers->value, 'caption' => 'Volunteers packing parcels']);

    $this->get(route('media'))->assertSee(route('media.collection', 'volunteers'), escape: false);
    $this->get(route('media.collection', 'volunteers'))->assertOk()->assertSee('Volunteers packing parcels');
    $this->get(route('media.collection', 'not_a_category'))->assertNotFound();
});

it('plays YouTube and Google Drive videos and shows Drive photos inside the gallery', function () {
    $event = publishedEvent();
    MediaItem::factory()->create(['event_id' => $event->id, 'type' => 'image', 'path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
    MediaItem::factory()->create(['event_id' => $event->id, 'type' => 'video', 'path' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view?usp=sharing']);
    MediaItem::factory()->create(['event_id' => $event->id, 'type' => 'image', 'path' => 'https://drive.google.com/file/d/1PhOtOiDxyz123456/view']);

    $photos = $this->get(route('media.event', $event))->assertOk()->viewData('album')['photos']->keyBy(fn ($p) => $p['embed'] ?? $p['url']);

    expect($photos->keys()->all())->toContain(
        'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0',
        'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/preview',
        'https://drive.google.com/thumbnail?id=1PhOtOiDxyz123456&sz=w2000',
    );
    expect($photos['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0'])
        ->type->toBe('video')
        ->thumb->toBe('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg');
    expect($photos['https://drive.google.com/thumbnail?id=1PhOtOiDxyz123456&sz=w2000'])
        ->type->toBe('image')
        ->thumb->toBe('https://drive.google.com/thumbnail?id=1PhOtOiDxyz123456&sz=w800');
});

it('reads the common YouTube and Google Drive link formats', function (string $url, ?string $provider, ?string $id) {
    $link = MediaLink::parse($url);

    expect($link?->provider)->toBe($provider)->and($link?->id)->toBe($id);
})->with([
    ['https://youtu.be/dQw4w9WgXcQ', 'youtube', 'dQw4w9WgXcQ'],
    ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30', 'youtube', 'dQw4w9WgXcQ'],
    ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'youtube', 'dQw4w9WgXcQ'],
    ['https://drive.google.com/file/d/1AbCdEfGhIjKlMnOp/view?usp=sharing', 'drive', '1AbCdEfGhIjKlMnOp'],
    ['https://drive.google.com/open?id=1AbCdEfGhIjKlMnOp', 'drive', '1AbCdEfGhIjKlMnOp'],
    ['https://example.com/watch?v=dQw4w9WgXcQ', null, null],
    ['https://www.youtube.com/watch?v=bad id', null, null],
    ['media/photo.jpg', null, null],
]);

it('links an event page to its full gallery', function () {
    $event = publishedEvent();
    EventImage::factory()->count(8)->create(['event_id' => $event->id]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('View full gallery (8)')
        ->assertSee(route('media.event', $event), escape: false)
        ->assertSee('+2 more');
});

it('saves YouTube links as videos in the admin media library', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test('admin.media')
        ->call('startCreate')
        ->set('type', 'image')
        ->set('category', MediaCategory::Events->value)
        ->set('path', 'https://youtu.be/dQw4w9WgXcQ')
        ->assertSee('YouTube video detected')
        ->call('save')
        ->assertHasNoErrors();

    expect(MediaItem::query()->sole())
        ->type->value->toBe('video')
        ->path->toBe('https://youtu.be/dQw4w9WgXcQ');
});

it('falls back to foundation photographs when there are no albums yet', function () {
    $this->get(route('media'))
        ->assertOk()
        ->assertSee('School supplies distribution day');
});

it('seeds demo albums for the demo events without duplicating them', function () {
    $this->seed(EventSeeder::class);
    $this->seed(DemoMediaSeeder::class);
    $this->seed(DemoMediaSeeder::class);

    expect(MediaItem::query()->count())->toBe(24);

    $albums = app(MediaAlbums::class)->all();
    expect($albums)->toHaveCount(7)
        ->and($albums->firstWhere('title', 'Christmas Food Distribution — Ho')['photos'])->toHaveCount(6);
});

it('offers search, year, country, programme and sort controls', function () {
    $this->seed(EventSeeder::class);
    $this->seed(DemoMediaSeeder::class);

    $this->get(route('media'))
        ->assertOk()
        ->assertSee('placeholder="Search albums…"', escape: false)
        ->assertSee('<option value="">All years</option>', escape: false)
        ->assertSee('<option value="Togo">Togo</option>', escape: false)
        ->assertSee('<option value="photos">Most photos</option>', escape: false)
        ->assertSee('All programmes')
        ->assertSee('Showing');
});

it('refuses to seed demo media in production', function () {
    app()->instance('env', 'production');

    expect(fn () => (new DemoMediaSeeder)->run())->toThrow(RuntimeException::class);
});
