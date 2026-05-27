<?php

namespace App\Models;

use App\Enums\TimelineEntryType;
use Database\Factories\BeneficiaryTimelineEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryTimelineEntry extends Model
{
    /** @use HasFactory<BeneficiaryTimelineEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'beneficiary_id', 'type', 'description', 'occurred_at', 'recorded_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'type' => TimelineEntryType::class,
        ];
    }

    /** @return BelongsTo<Beneficiary, $this> */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
