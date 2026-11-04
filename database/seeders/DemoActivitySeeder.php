<?php

namespace Database\Seeders;

use App\Enums\NewsletterCampaignStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use App\Models\PageVisit;
use App\Models\SiteConversion;
use App\Models\Volunteer;
use App\Models\VolunteerHourLog;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Sample activity so reviewers can see the volunteer hours, event sign-up,
 * newsletter and analytics screens with data in them. Run after the other demo seeders.
 * Safe to rerun, and every address is on example.com, so nothing can reach a real person.
 */
class DemoActivitySeeder extends Seeder
{
    private const ANALYTICS_CAMPAIGN = 'demo-seed';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo activity must not be seeded in production.');
        }

        $this->seedVolunteerHours();
        $this->seedEventRegistrations();
        $this->seedNewsletters();
        $this->seedAnalytics();
    }

    private function seedVolunteerHours(): void
    {
        $events = Event::query()->whereIn('slug', ['christmas-food-distribution-ho', 'orphanage-visit-kpalime'])->get();
        $volunteers = Volunteer::query()->where('email', 'like', '%@example.com')->orderBy('id')->limit(6)->get();

        foreach ($events as $event) {
            foreach ($volunteers as $index => $volunteer) {
                VolunteerHourLog::query()->updateOrCreate(
                    ['volunteer_id' => $volunteer->id, 'event_id' => $event->id],
                    ['hours' => 4 + $index, 'served_on' => $event->starts_at->toDateString(), 'notes' => 'Demo record.'],
                );
            }
        }
    }

    private function seedEventRegistrations(): void
    {
        $attendees = ['Ada Lartey', 'Ben Kumah', 'Cynthia Afful', 'David Ansah', 'Eunice Baah', 'Felix Opoku'];

        $events = Event::query()->whereIn('slug', [
            'community-outreach-program-volta-region',
            'womens-skills-and-micro-business-workshop-accra',
            'annual-fundraising-dinner-washington-dc',
        ])->get();

        foreach ($events as $event) {
            $event->update(['registration_enabled' => true, 'capacity' => 150]);

            foreach ($attendees as $index => $name) {
                EventRegistration::query()->updateOrCreate(
                    ['event_id' => $event->id, 'email' => str($name)->slug('.').'@example.com'],
                    ['full_name' => $name, 'party_size' => $index % 3 + 1, 'consented_at' => now()],
                );
            }
        }
    }

    private function seedNewsletters(): void
    {
        $subscribers = collect(['Abigail Ofosu', 'Bernard Sowah', 'Comfort Adjei', 'Desmond Yeboah', 'Elinam Dotse', 'Francis Nartey', 'Gloria Amoah', 'Henry Boadu'])
            ->map(fn (string $name, int $index) => NewsletterSubscriber::query()->updateOrCreate(
                ['email' => str($name)->slug('.').'@example.com'],
                [
                    'name' => $name,
                    'subscribed_at' => now()->subDays(($index + 1) * 6),
                    'unsubscribed_at' => $index === 7 ? now()->subDays(2) : null,
                    'source' => $index % 2 === 0 ? 'home' : 'events',
                ],
            ));

        NewsletterCampaign::query()->updateOrCreate(
            ['subject' => 'Demo draft: our next outreach in the Volta Region'],
            ['body' => "Hello friends,\n\nThis is a **sample draft** to show how a newsletter is written and previewed.", 'status' => NewsletterCampaignStatus::Draft],
        );

        $sent = NewsletterCampaign::query()->updateOrCreate(
            ['subject' => 'Demo: thank you for a wonderful year'],
            [
                'body' => "Hello friends,\n\nThis is a **sample newsletter** to show how a sent campaign looks.",
                'status' => NewsletterCampaignStatus::Sent,
                'started_at' => now()->subDays(5),
                'finished_at' => now()->subDays(5)->addMinutes(2),
            ],
        );

        foreach ($subscribers as $subscriber) {
            NewsletterCampaignRecipient::query()->updateOrCreate(
                ['newsletter_campaign_id' => $sent->id, 'email' => $subscriber->email],
                ['newsletter_subscriber_id' => $subscriber->id, 'status' => 'sent', 'sent_at' => now()->subDays(5)],
            );
        }
    }

    /**
     * Thirty days of sample traffic, tagged with a campaign so a rerun replaces it.
     */
    private function seedAnalytics(): void
    {
        PageVisit::query()->where('campaign', self::ANALYTICS_CAMPAIGN)->delete();
        SiteConversion::query()->where('campaign', self::ANALYTICS_CAMPAIGN)->delete();

        $paths = ['/', '/', '/', '/events', '/sponsor-a-child', '/about', '/volunteer', '/contact'];
        $sources = [['direct', null], ['direct', null], ['google', 'organic'], ['facebook', 'social'], ['instagram', 'social'], ['newsletter', 'email']];
        $conversions = ['sponsorship_request', 'donation_enquiry', 'volunteer_application', 'newsletter_signup', 'event_registration'];

        $visits = [];
        $signups = [];

        for ($day = 30; $day >= 0; $day--) {
            $visitsToday = 20 + ($day * 7) % 25;

            for ($visit = 0; $visit < $visitsToday; $visit++) {
                [$source, $medium] = $sources[($day + $visit) % count($sources)];

                $visits[] = [
                    'path' => $paths[($day * 3 + $visit) % count($paths)],
                    'source' => $source,
                    'medium' => $medium,
                    'campaign' => self::ANALYTICS_CAMPAIGN,
                    'visitor_hash' => hash('sha256', "demo-{$day}-".($visit % 15)),
                    'created_at' => today()->subDays($day)->addMinutes(480 + $visit * 17),
                ];
            }

            if ($day % 2 === 0) {
                [$source, $medium] = $sources[$day % count($sources)];

                $signups[] = [
                    'type' => $conversions[$day % count($conversions)],
                    'source' => $source,
                    'medium' => $medium,
                    'campaign' => self::ANALYTICS_CAMPAIGN,
                    'created_at' => today()->subDays($day)->addHours(12),
                ];
            }
        }

        foreach (array_chunk($visits, 200) as $chunk) {
            PageVisit::query()->insert($chunk);
        }

        SiteConversion::query()->insert($signups);
    }
}
