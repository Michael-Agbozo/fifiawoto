<?php

namespace App\Console\Commands;

use App\Mail\SponsorshipReminder;
use App\Models\Sponsorship;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSponsorshipReminders extends Command
{
    /** Days before the due date that the friendly reminder goes out. */
    public const DAYS_BEFORE_DUE = 3;

    /** Days past the due date that the single follow-up goes out. */
    public const DAYS_OVERDUE = 7;

    protected $signature = 'sponsorships:send-reminders';

    protected $description = 'Email sponsors whose monthly pledge is coming up or a week overdue (at most once each per due date)';

    public function handle(): int
    {
        $sent = 0;

        Sponsorship::query()->active()->where('reminders_enabled', true)->whereNotNull('next_due_on')
            ->with('beneficiary')
            ->eachById(function (Sponsorship $sponsorship) use (&$sent): void {
                $due = $sponsorship->next_due_on;
                $isUpcoming = $due->between(today(), today()->addDays(self::DAYS_BEFORE_DUE))
                    && ! $sponsorship->due_reminder_sent_for?->equalTo($due);
                $isOverdue = $due->lte(today()->subDays(self::DAYS_OVERDUE))
                    && ! $sponsorship->overdue_reminder_sent_for?->equalTo($due);

                if (! $isUpcoming && ! $isOverdue) {
                    return;
                }

                Mail::to($sponsorship->sponsor_email)->queue(new SponsorshipReminder($sponsorship));

                $sponsorship->update(array_filter([
                    'due_reminder_sent_for' => $isUpcoming ? $due : null,
                    'overdue_reminder_sent_for' => $isOverdue ? $due : null,
                    'last_reminded_at' => now(),
                ]));
                $sent++;
            });

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
