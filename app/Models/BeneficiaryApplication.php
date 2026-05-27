<?php

namespace App\Models;

use App\Enums\AssistanceType;
use App\Enums\BeneficiaryApplicationStatus;
use Database\Factories\BeneficiaryApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeneficiaryApplication extends Model
{
    /** @use HasFactory<BeneficiaryApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name', 'phone', 'email', 'country', 'region',
        'assistance_type', 'situation', 'status',
        'reviewer_id', 'reviewed_at', 'converted_beneficiary_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'assistance_type' => AssistanceType::class,
            'status' => BeneficiaryApplicationStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return BelongsTo<Beneficiary, $this> */
    public function convertedBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class, 'converted_beneficiary_id');
    }

    /** @return HasMany<BeneficiaryApplicationDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(BeneficiaryApplicationDocument::class);
    }
}
