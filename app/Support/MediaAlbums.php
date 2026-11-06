<?php

namespace App\Support;

use App\Enums\EventStatus;
use App\Enums\MediaCategory;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\MediaItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Groups gallery media into albums: one per published event (an outreach,
 * donation drive or programme day, with its date and place), plus one per
 * category for media that isn't tied to any event. Media tied to an unpublished
 * event stays hidden until that event goes live. Newest first.
 *
 * @phpstan-type Photo array{type: string, url: ?string, thumb: ?string, embed: ?string, caption: ?string}
 * @phpstan-type Album array{key: string, title: string, kind: string, category: string, date: ?CarbonInterface, year: ?int, place: ?string, country: ?string, gallery_url: string, event_url: ?string, photos: Collection<int, Photo>}
 */
class MediaAlbums
{
    /** @var Collection<int, Album>|null */
    private ?Collection $albums = null;

    /** @return Collection<int, Album> */
    public function all(): Collection
    {
        return $this->albums ??= $this->build();
    }

    /** @return Album|null */
    public function forEvent(Event $event): ?array
    {
        return $this->all()->firstWhere('key', 'event-'.$event->id);
    }

    /** @return Album|null */
    public function forCategory(MediaCategory $category): ?array
    {
        return $this->all()->firstWhere('key', 'category-'.$category->value);
    }

    /** @return Collection<int, Album> */
    private function build(): Collection
    {
        $columns = 'event:id,slug,title,status,published_at,starts_at,location,country';

        $items = MediaItem::query()->with($columns)->orderBy('sort')->orderByDesc('id')->get();

        $eventPhotos = EventImage::query()
            ->whereHas('event', fn ($events) => $events->published())
            ->with($columns)
            ->orderBy('sort')
            ->get();

        $isPublic = fn (?Event $event): bool => $event !== null
            && $event->status === EventStatus::Published
            && ($event->published_at === null || $event->published_at->lte(now()));

        $eventAlbums = $items->filter(fn (MediaItem $item) => $isPublic($item->event))
            ->map(fn (MediaItem $item) => ['event' => $item->event, 'category' => $item->category, 'photo' => $this->photo($item->type->value, $item->path, $item->poster_path, $item->caption)])
            ->concat($eventPhotos->map(fn (EventImage $image) => ['event' => $image->event, 'category' => null, 'photo' => $this->photo('image', $image->path, null, $image->caption)]))
            ->groupBy(fn (array $entry) => $entry['event']->id)
            ->map(function (Collection $entries) {
                /** @var Event $event */
                $event = $entries->first()['event'];

                return $this->album(
                    key: 'event-'.$event->id,
                    title: $event->title,
                    kind: 'Outreach',
                    category: $entries->pluck('category')->filter()->first()?->value ?? MediaCategory::Events->value,
                    date: $event->starts_at,
                    place: collect([$event->location, $event->country])->filter()->unique()->implode(', ') ?: null,
                    country: $event->country,
                    galleryUrl: route('media.event', $event),
                    eventUrl: route('events.show', $event),
                    photos: $entries->pluck('photo')->values(),
                );
            });

        $categoryAlbums = $items->whereNull('event_id')
            ->groupBy(fn (MediaItem $item) => $item->category->value)
            ->map(fn (Collection $group, string $category) => $this->album(
                key: 'category-'.$category,
                title: MediaCategory::from($category)->label(),
                kind: 'Programme',
                category: $category,
                date: $group->max('created_at'),
                place: null,
                country: null,
                galleryUrl: route('media.collection', $category),
                eventUrl: null,
                photos: $group->map(fn (MediaItem $item) => $this->photo($item->type->value, $item->path, $item->poster_path, $item->caption))->values(),
            ));

        return $eventAlbums->concat($categoryAlbums)
            ->sortByDesc(fn (array $album) => $album['date']?->timestamp ?? 0)
            ->values();
    }

    /**
     * @param  Collection<int, Photo>  $photos
     * @return Album
     */
    private function album(string $key, string $title, string $kind, string $category, ?CarbonInterface $date, ?string $place, ?string $country, string $galleryUrl, ?string $eventUrl, Collection $photos): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'kind' => $kind,
            'category' => $category,
            'date' => $date,
            'year' => $date?->year,
            'place' => $place,
            'country' => $country,
            'gallery_url' => $galleryUrl,
            'event_url' => $eventUrl,
            'photos' => $photos,
        ];
    }

    /**
     * Shape one gallery item. YouTube and Google Drive links become embeddable
     * videos or directly displayable photos; uploads resolve through MediaUrl.
     *
     * @return Photo
     */
    private function photo(string $type, ?string $path, ?string $poster, ?string $caption): array
    {
        $link = MediaLink::parse($path);

        if ($link?->isYouTube()) {
            $type = 'video';
        }

        return [
            'type' => $type,
            'url' => $link ? ($type === 'video' ? $link->embedUrl() : $link->imageUrl(2000)) : MediaUrl::for($path),
            'thumb' => MediaUrl::for($poster) ?? ($link ? $link->imageUrl() : MediaUrl::for($path)),
            'embed' => $link && $type === 'video' ? $link->embedUrl() : null,
            'caption' => $caption,
        ];
    }
}
