<?php

namespace App\Models;

use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use Database\Factories\BeneficiaryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    /** @use HasFactory<BeneficiaryFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'full_name', 'date_of_birth', 'gender', 'phone', 'email', 'country',
        'region', 'category', 'description', 'status', 'assigned_to_user_id',
        'photo_path', 'notes', 'source_application_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'category' => SupportCategory::class,
            'status' => SupportStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /** @return BelongsTo<BeneficiaryApplication, $this> */
    public function sourceApplication(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryApplication::class, 'source_application_id');
    }

    /** @return HasMany<BeneficiaryFolder, $this> */
    public function folders(): HasMany
    {
        return $this->hasMany(BeneficiaryFolder::class);
    }

    /** @return HasMany<BeneficiaryDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(BeneficiaryDocument::class);
    }

    /** @return HasMany<BeneficiaryTimelineEntry, $this> */
    public function timeline(): HasMany
    {
        return $this->hasMany(BeneficiaryTimelineEntry::class)->orderByDesc('occurred_at');
    }

    /** @param Builder<Beneficiary> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [SupportStatus::Approved->value, SupportStatus::Active->value]);
    }
}
