<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'donor_name',
        'donor_email',
        'amount_cents',
        'currency',
        'payment_method',
        'external_reference',
        'received_at',
        'recorded_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'received_at' => 'date',
        ];
    }

    /**
     * Restrict to gifts recorded in the foundation's base currency so
     * aggregates never mix amounts in different currencies.
     *
     * @param  Builder<Donation>  $query
     */
    public function scopeInBaseCurrency(Builder $query): void
    {
        $query->where($query->qualifyColumn('currency'), Money::baseCurrency());
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
