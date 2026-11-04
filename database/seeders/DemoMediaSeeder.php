<?php

namespace Database\Seeders;

use App\Enums\MediaCategory;
use App\Enums\MediaType;
use App\Models\Event;
use App\Models\MediaItem;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo albums for local and staging sites: the demo events from EventSeeder
 * get foundation photographs, plus two programme collections. Uses images
 * already bundled in public/images/foundation (never the stock community-*.jpg
 * placeholders), and is safe to rerun.
 */
class DemoMediaSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo media must not be seeded in production.');
        }

        $albums = [
            'community-outreach-program-volta-region' => [MediaCategory::CommunityOutreach, [
                ['school-donation.jpg', 'Books and school bags handed out on day one'],
                ['outreach-1.jpg', 'Volunteers setting up the distribution point'],
                ['outreach-2.webp', 'Families at the mobile health clinic'],
                ['outreach-4.webp', 'Packing food parcels for 200 families'],
                ['about/empowering-hope.jpg', 'Children with their new supplies'],
            ]],
            'back-to-school-supplies-drive-lome' => [MediaCategory::EducationPrograms, [
                ['outreach-1.jpg', 'Uniform fittings in Lomé'],
                ['school-donation.jpg', 'Stationery packs ready for the new term'],
                ['outreach-3.webp', 'Head teachers welcoming the team'],
            ]],
            'widows-care-and-health-screening-cotonou' => [MediaCategory::CommunityOutreach, [
                ['about/serving-with-heart.jpg', 'Health checks for widows in Cotonou'],
                ['outreach-3.webp', 'Care packages being shared'],
                ['outreach-2.webp', 'Sharing lunch after the screening'],
                ['about/team-group.jpg', 'Community leaders who hosted the day'],
            ]],
            'christmas-food-distribution-ho' => [MediaCategory::Events, [
                ['outreach-4.webp', 'Festive food parcels for families in Ho'],
                ['about/empowering-hope.jpg', 'Gifts for the children'],
                ['about/team-group.jpg', 'The team after the distribution'],
                ['school-donation.jpg', 'A visit to the local school'],
                ['outreach-2.webp', 'Families collecting parcels'],
                ['outreach-1.jpg', 'Community meal'],
            ]],
            'orphanage-visit-kpalime' => [MediaCategory::Volunteers, [
                ['about/empowering-hope.jpg', 'Games and storytelling with the children'],
                ['outreach-1.jpg', 'Volunteers delivering clothes and toiletries'],
            ]],
        ];

        foreach ($albums as $slug => [$category, $photos]) {
            $event = Event::query()->where('slug', $slug)->first();

            if (! $event) {
                continue;
            }

            foreach ($photos as $sort => [$file, $caption]) {
                $this->photo($category, 'images/foundation/'.$file, $caption, $sort, $event->id);
            }
        }

        // Programme collections: photos not tied to a single event.
        foreach ([
            [MediaCategory::Volunteers, 'about/team-group.jpg', 'Our volunteer team in Ghana'],
            [MediaCategory::Volunteers, 'outreach-4.webp', 'Volunteers preparing care packs'],
            [MediaCategory::EducationPrograms, 'school-donation.jpg', 'Classroom visits through the year'],
            [MediaCategory::EducationPrograms, 'about/empowering-hope.jpg', 'Pupils supported by the foundation'],
        ] as $sort => [$category, $file, $caption]) {
            $this->photo($category, 'images/foundation/'.$file, $caption, $sort, null);
        }
    }

    private function photo(MediaCategory $category, string $path, string $caption, int $sort, ?int $eventId): void
    {
        MediaItem::query()->updateOrCreate(
            ['event_id' => $eventId, 'path' => $path, 'category' => $category->value],
            ['type' => MediaType::Image->value, 'disk' => 'public', 'caption' => $caption, 'sort' => $sort],
        );
    }
}
