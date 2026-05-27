<?php

namespace Database\Seeders;

use App\Enums\InstagramSource;
use App\Models\InstagramPost;
use Illuminate\Database\Seeder;

/**
 * Seed curated highlight posts for the "Follow Our Journey" slider on the
 * public home page. These use foundation imagery as thumbnails until real
 * Instagram Graph API sync is wired up — the captions and links resemble
 * actual @the_fifiawotofoundation activity.
 */
class InstagramHighlightSeeder extends Seeder
{
    public function run(): void
    {
        $profileUrl = rtrim(config('social.instagram', 'https://www.instagram.com/the_fifiawotofoundation/'), '/').'/';

        $highlights = [
            [
                'caption' => 'Community gathering in the Volta region — our team and partners came together to plan the next season of outreach.',
                'thumbnail_url' => asset('images/foundation/community-1.jpg'),
                'media_type' => 'IMAGE',
                'posted_at' => now()->subDays(3),
            ],
            [
                'caption' => 'Back-to-school distribution day! Notebooks, uniforms, and a warm welcome for every child.',
                'thumbnail_url' => asset('images/foundation/school-donation.jpg'),
                'media_type' => 'IMAGE',
                'posted_at' => now()->subDays(8),
            ],
            [
                'caption' => 'Outreach kickoff with local partners across Greater Accra.',
                'thumbnail_url' => asset('images/foundation/outreach-1.jpg'),
                'media_type' => 'IMAGE',
                'posted_at' => now()->subDays(14),
            ],
            [
                'caption' => 'Mobile health clinic in action — providing free screenings and care for families.',
                'thumbnail_url' => asset('images/foundation/outreach-2.webp'),
                'media_type' => 'VIDEO',
                'posted_at' => now()->subDays(21),
            ],
            [
                'caption' => 'Widows empowerment workshop — a moving day of stories, skills, and sisterhood.',
                'thumbnail_url' => asset('images/foundation/outreach-3.webp'),
                'media_type' => 'IMAGE',
                'posted_at' => now()->subDays(28),
            ],
            [
                'caption' => 'Greater Accra town-hall with community leaders to map the next year of programs.',
                'thumbnail_url' => asset('images/foundation/community-2.jpg'),
                'media_type' => 'IMAGE',
                'posted_at' => now()->subDays(35),
            ],
            [
                'caption' => 'Volunteers prepping care packs ahead of our weekend distribution.',
                'thumbnail_url' => asset('images/foundation/outreach-4.webp'),
                'media_type' => 'IMAGE',
                'posted_at' => now()->subDays(42),
            ],
            [
                'caption' => 'Annual gathering with the partners and donors who make this work possible.',
                'thumbnail_url' => asset('images/foundation/community-3.jpg'),
                'media_type' => 'IMAGE',
                'posted_at' => now()->subDays(49),
            ],
        ];

        foreach ($highlights as $i => $data) {
            // Until the Instagram Graph API sync is live, every seeded highlight
            // links to the foundation's real profile feed. That's truthful — the
            // visitor lands on the page where they can actually see these posts,
            // rather than on a dead query-string URL pretending to be a permalink.
            InstagramPost::query()->updateOrCreate(
                ['external_id' => sha1('seed:'.$i)],
                [
                    'permalink' => $profileUrl,
                    'caption' => $data['caption'],
                    'thumbnail_url' => $data['thumbnail_url'],
                    'media_type' => $data['media_type'],
                    'posted_at' => $data['posted_at'],
                    'is_approved' => true,
                    'is_hidden' => false,
                    'source' => InstagramSource::Manual->value,
                ],
            );
        }
    }
}
