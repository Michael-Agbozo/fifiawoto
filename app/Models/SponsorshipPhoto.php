<?php

namespace App\Models;

use Database\Factories\SponsorshipPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A photograph in a child's public sponsorship gallery. Photos marked
 * sensitive are shown blurred until the visitor chooses to view them.
 */
class SponsorshipPhoto extends Model
{
    /** @use HasFactory<SponsorshipPhotoFactory> */
    use HasFactory;

    /** Most photos a single child's gallery may hold. */
    public const MAX_PER_CHILD = 12;

    protected $fillable = ['beneficiary_id', 'path', 'caption', 'is_sensitive', 'sort'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /** @return BelongsTo<Beneficiary, $this> */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
