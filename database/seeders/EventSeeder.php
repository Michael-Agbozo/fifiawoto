<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\PaymentMethod;
use App\Models\Donation;
use App\Models\Event;
use App\Support\Money;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo events for local and staging sites: upcoming fundraisers at different
 * stages of funding, a couple of past events and one draft, each with a
 * foundation photograph and recorded gifts. Safe to run repeatedly — events
 * are keyed by slug and gifts by their seed reference.
 */
class EventSeeder extends Seeder
{
    /** @return list<string> */
    public static function slugs(): array
    {
        return array_column((new self)->events(), 'slug');
    }

    public function run(): void
    {
        if (app()->isProduction() && ! config('demo.live_content')) {
            throw new RuntimeException('Demo events must not be seeded in production.');
        }

        foreach ($this->events() as $event) {
            $raisedCents = $event['raised_cents'];
            unset($event['raised_cents']);

            $model = Event::query()->updateOrCreate(['slug' => $event['slug']], $event);

            $this->seedGifts($model, $raisedCents);
        }
    }

    /**
     * Record the event's gifts as a handful of donations that add up to the raised total.
     */
    private function seedGifts(Event $event, int $raisedCents): void
    {
        $shares = [0.4, 0.25, 0.15, 0.12, 0.08];
        $donors = ['Akosua Mensah', 'The Adabla Family', 'Grace Asante', 'Daniel Owusu', 'Anonymous'];
        $methods = [PaymentMethod::BankTransfer, PaymentMethod::MobileMoney, PaymentMethod::Card, PaymentMethod::Cash, PaymentMethod::Card];
        $remaining = $raisedCents;

        foreach ($shares as $index => $share) {
            $reference = 'SEED-'.$event->slug.'-'.($index + 1);
            $amount = $index === count($shares) - 1 ? $remaining : (int) round($raisedCents * $share);
            $remaining -= $amount;

            if ($amount <= 0) {
                Donation::query()->where('external_reference', $reference)->delete();

                continue;
            }

            Donation::query()->updateOrCreate(['external_reference' => $reference], [
                'event_id' => $event->id,
                'donor_name' => $donors[$index],
                'donor_email' => null,
                'amount_cents' => $amount,
                'currency' => Money::baseCurrency(),
                'payment_method' => $methods[$index]->value,
                'received_at' => now()->subDays(3 + $index * 4)->toDateString(),
                'notes' => 'Demo gift',
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(): array
    {
        $published = ['status' => EventStatus::Published->value, 'published_at' => now()->subDays(10)];

        return [
            [
                'slug' => 'community-outreach-program-volta-region',
                'title' => 'Community Outreach Program — Volta Region',
                'starts_at' => now()->addWeeks(2)->setTime(9, 0),
                'ends_at' => now()->addWeeks(2)->addDays(2)->setTime(17, 0),
                'location' => 'Ho, Volta Region',
                'country' => 'Ghana',
                'description' => 'A three-day outreach bringing food, school supplies and health checks to vulnerable families across the Volta Region, run with our partner churches.',
                'activities' => "Food parcels for 200 families\nSchool bags and books for school-aged children\nMobile health clinic\nWomen's empowerment workshop",
                'expected_impact' => 'Direct support for at least 300 community members, with follow-up visits through partner churches.',
                'volunteer_opportunities' => "Logistics and set-up\nProgramme facilitation\nMedia and photography\nChild care during workshops",
                'goal_cents' => 10_000_00,
                'raised_cents' => 4_300_00,
                'hero_image_path' => 'images/foundation/school-donation.jpg',
                ...$published,
            ],
            [
                'slug' => 'back-to-school-supplies-drive-lome',
                'title' => 'Back-to-School Supplies Drive — Lomé',
                'starts_at' => now()->addWeeks(4)->setTime(8, 30),
                'ends_at' => now()->addWeeks(4)->setTime(15, 0),
                'location' => 'Lomé',
                'country' => 'Togo',
                'description' => 'Uniforms, shoes, books and school bags for 250 children starting the new school year, handed out with local head teachers.',
                'activities' => "Uniform and shoe fittings\nBook and stationery packs\nReading corner for younger children\nParent information session",
                'expected_impact' => '250 children start the school year fully equipped.',
                'volunteer_opportunities' => "Packing school kits\nFittings and sizing\nRegistration desk",
                'goal_cents' => 15_000_00,
                'raised_cents' => 9_000_00,
                'hero_image_path' => 'images/foundation/outreach-1.jpg',
                ...$published,
            ],
            [
                'slug' => 'widows-care-and-health-screening-cotonou',
                'title' => "Widows' Care & Health Screening — Cotonou",
                'starts_at' => now()->addWeeks(6)->setTime(9, 0),
                'ends_at' => now()->addWeeks(6)->setTime(16, 0),
                'location' => 'Cotonou',
                'country' => 'Benin',
                'description' => 'A day of free health screening, care packages and companionship for widows and elderly women, with follow-up referrals to local clinics.',
                'activities' => "Blood pressure and sugar checks\nEye tests and reading glasses\nCare packages\nShared lunch",
                'expected_impact' => '150 women screened and referred for any follow-up care they need.',
                'volunteer_opportunities' => "Nurses and health workers\nCare package assembly\nHospitality",
                'goal_cents' => 8_000_00,
                'raised_cents' => 6_400_00,
                'hero_image_path' => 'images/foundation/about/serving-with-heart.jpg',
                ...$published,
            ],
            [
                'slug' => 'womens-skills-and-micro-business-workshop-accra',
                'title' => "Women's Skills & Micro-Business Workshop — Accra",
                'starts_at' => now()->addWeeks(8)->setTime(10, 0),
                'ends_at' => now()->addWeeks(8)->addDay()->setTime(16, 0),
                'location' => 'Accra',
                'country' => 'Ghana',
                'description' => 'Two days of hands-on training in bookkeeping, soap-making and tailoring, with starter kits so participants can begin trading straight away.',
                'activities' => "Bookkeeping basics\nSoap-making and tailoring sessions\nStarter kit handover\nMentor matching",
                'expected_impact' => '60 women leave with a starter kit and a mentor.',
                'volunteer_opportunities' => "Trainers and mentors\nWorkshop assistants\nTranslation",
                'goal_cents' => 12_000_00,
                'raised_cents' => 2_400_00,
                'hero_image_path' => 'images/foundation/community-2.jpg',
                ...$published,
            ],
            [
                'slug' => 'annual-fundraising-dinner-washington-dc',
                'title' => 'Annual Fundraising Dinner — Washington, DC',
                'starts_at' => now()->addWeeks(10)->setTime(18, 30),
                'ends_at' => now()->addWeeks(10)->setTime(22, 0),
                'location' => 'Washington, DC',
                'country' => 'United States',
                'description' => 'An evening with supporters, stories from the field and a silent auction, raising funds for next year\'s programmes in West Africa.',
                'activities' => "Dinner and live music\nStories from programme leads\nSilent auction",
                'expected_impact' => 'Funds the foundation\'s programme calendar for the coming year.',
                'volunteer_opportunities' => "Guest welcome\nAuction desk\nEvent photography",
                'goal_cents' => 25_000_00,
                'raised_cents' => 11_250_00,
                'hero_image_path' => 'images/foundation/about/team-group.jpg',
                ...$published,
            ],
            [
                'slug' => 'christmas-food-distribution-ho',
                'title' => 'Christmas Food Distribution — Ho',
                'starts_at' => now()->subMonths(10)->setTime(9, 0),
                'ends_at' => now()->subMonths(10)->setTime(15, 0),
                'location' => 'Ho, Volta Region',
                'country' => 'Ghana',
                'description' => 'Festive food parcels and gifts for families and children across Ho and the surrounding villages.',
                'activities' => "Food parcels\nGifts for children\nCommunity meal",
                'expected_impact' => 'Reached 180 families over the festive season.',
                'volunteer_opportunities' => 'Packing and distribution',
                'goal_cents' => 6_000_00,
                'raised_cents' => 6_600_00,
                'hero_image_path' => 'images/foundation/outreach-4.webp',
                ...$published,
            ],
            [
                'slug' => 'orphanage-visit-kpalime',
                'title' => 'Orphanage Visit — Kpalimé',
                'starts_at' => now()->subMonths(5)->setTime(10, 0),
                'ends_at' => now()->subMonths(5)->setTime(14, 0),
                'location' => 'Kpalimé',
                'country' => 'Togo',
                'description' => 'A day of games, shared meals and donated essentials with the children and carers of a partner orphanage.',
                'activities' => "Games and storytelling\nShared lunch\nDonated clothes and toiletries",
                'expected_impact' => 'Essentials delivered for 45 children.',
                'volunteer_opportunities' => 'Activity leaders',
                'goal_cents' => null,
                'raised_cents' => 0,
                'hero_image_path' => 'images/foundation/community-1.jpg',
                ...$published,
            ],
            [
                'slug' => 'clean-water-borehole-project',
                'title' => 'Clean Water Borehole Project',
                'starts_at' => now()->addMonths(5)->setTime(9, 0),
                'ends_at' => now()->addMonths(5)->addDays(5)->setTime(17, 0),
                'location' => 'To be confirmed',
                'country' => 'Benin',
                'description' => 'Planning a borehole and hand pump for a village without safe drinking water. Details are still being agreed with community leaders.',
                'activities' => "Site survey\nDrilling and installation\nWater committee training",
                'expected_impact' => 'Safe water within walking distance for an entire village.',
                'volunteer_opportunities' => 'To be announced',
                'goal_cents' => 30_000_00,
                'raised_cents' => 0,
                'hero_image_path' => 'images/foundation/community-3.jpg',
                'status' => EventStatus::Draft->value,
                'published_at' => null,
            ],
        ];
    }
}
