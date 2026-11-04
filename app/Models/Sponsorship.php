<?php

namespace App\Models;

use App\Enums\SponsorshipStatus;
use Carbon\CarbonInterface;
use Database\Factories\SponsorshipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sponsorship extends Model
{
    /** @use HasFactory<SponsorshipFactory> */
    use HasFactory;

    protected $fillable = [
        'beneficiary_id', 'contact_message_id', 'sponsor_name', 'sponsor_email', 'sponsor_phone',
        'country', 'mailing_address', 'monthly_amount_cents', 'one_time_gift_cents', 'currency',
        'status', 'started_on', 'next_due_on', 'ended_on', 'reminders_enabled',
        'due_reminder_sent_for', 'overdue_reminder_sent_for', 'last_reminded_at', 'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SponsorshipStatus::class,
            'monthly_amount_cents' => 'integer',
            'one_time_gift_cents' => 'integer',
            'started_on' => 'date',
            'next_due_on' => 'date',
            'ended_on' => 'date',
            'reminders_enabled' => 'boolean',
            'due_reminder_sent_for' => 'date',
            'overdue_reminder_sent_for' => 'date',
            'last_reminded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Beneficiary, $this> */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class)->withTrashed();
    }

    /** @return HasMany<Donation, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /** @param Builder<Sponsorship> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', SponsorshipStatus::Active->value);
    }

    /** @param Builder<Sponsorship> $query */
    public function scopeOverdue(Builder $query): void
    {
        $query->active()->whereDate('next_due_on', '<', today()->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->status === SponsorshipStatus::Active
            && $this->next_due_on !== null
            && $this->next_due_on->lt(today());
    }

    /**
     * Start the pledge. The first month falls due on the start date, unless payments are already logged.
     */
    public function activate(CarbonInterface $startsOn): void
    {
        $this->forceFill([
            'status' => SponsorshipStatus::Active,
            'started_on' => $startsOn->toDateString(),
            'ended_on' => null,
        ]);
        $this->next_due_on = $this->calculateNextDueOn();
        // The activation email already announces the first due date.
        $this->due_reminder_sent_for = $this->next_due_on;
        $this->save();
    }

    /**
     * Re-derive the next due date from the months actually paid, so editing or deleting
     * a linked donation can never leave the pledge out of step with its payments.
     */
    public function syncNextDueOn(): void
    {
        if ($this->started_on === null) {
            return;
        }

        $this->update(['next_due_on' => $this->calculateNextDueOn()]);
    }

    private function calculateNextDueOn(): CarbonInterface
    {
        return $this->started_on->copy()->addMonthsNoOverflow((int) $this->payments()->sum('months_covered'));
    }
}
