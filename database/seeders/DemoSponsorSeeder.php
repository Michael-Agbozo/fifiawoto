<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\SponsorshipStatus;
use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Sponsorship;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Sample sponsors for the demo children from DemoSponsorshipSeeder. Safe to rerun.
 * Every address is on example.com and reminders are off, so no email can reach anyone.
 */
class DemoSponsorSeeder extends Seeder
{
    /**
     * One pledge in each state staff work with: on track, due soon, overdue, new request and ended.
     */
    public function run(): void
    {
        if (app()->isProduction() && ! config('demo.live_content')) {
            throw new RuntimeException('Demo sponsors must not be seeded in production.');
        }

        $pledges = [
            ['Ama (Demo)', 'Hannah Whitfield', 5000, SponsorshipStatus::Active, today()->subMonths(3)->addDays(12), 3],
            ['Kofi (Demo)', 'Marcus Bell', 4000, SponsorshipStatus::Active, today()->subMonths(2)->addDays(2), 2],
            ['Abena (Demo)', 'Priya Nair', 7500, SponsorshipStatus::Active, today()->subMonths(3)->subDays(9), 3],
            ['Kwame (Demo)', 'Thomas Reed', 5000, SponsorshipStatus::Pending, null, 0],
            ['Esi (Demo)', 'Laura Mensah', 6000, SponsorshipStatus::Ended, today()->subMonths(8), 6],
        ];

        foreach ($pledges as [$child, $sponsor, $monthlyCents, $status, $startedOn, $monthsPaid]) {
            $beneficiary = Beneficiary::query()->where('public_display_name', $child)->first();

            if (! $beneficiary) {
                continue;
            }

            $email = str($sponsor)->slug('.').'@example.com';

            $sponsorship = Sponsorship::query()->updateOrCreate(
                ['beneficiary_id' => $beneficiary->id, 'sponsor_email' => $email],
                [
                    'sponsor_name' => $sponsor,
                    'country' => 'United States',
                    'monthly_amount_cents' => $monthlyCents,
                    'currency' => 'USD',
                    'status' => $status,
                    'started_on' => $startedOn,
                    'next_due_on' => $startedOn,
                    'ended_on' => $status === SponsorshipStatus::Ended ? today()->subMonths(2) : null,
                    'reminders_enabled' => false,
                    'notes' => 'Demo record.',
                ],
            );

            for ($month = 0; $month < $monthsPaid; $month++) {
                Donation::query()->updateOrCreate(
                    ['external_reference' => "SEED-SPONSOR-{$sponsorship->id}-{$month}"],
                    [
                        'sponsorship_id' => $sponsorship->id,
                        'donor_name' => $sponsor,
                        'donor_email' => $email,
                        'amount_cents' => $monthlyCents,
                        'currency' => 'USD',
                        'payment_method' => PaymentMethod::BankTransfer->value,
                        'months_covered' => 1,
                        'received_at' => $startedOn->copy()->addMonthsNoOverflow($month),
                    ],
                );
            }
        }
    }
}
