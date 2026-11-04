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
use Illuminate\Support\Str;

class Beneficiary extends Model
{
    /** Validation rules for a link to the beneficiary's Google Drive folder. */
    public const DRIVE_FOLDER_URL_RULES = ['nullable', 'string', 'max:500', 'url:https', 'regex:#^https://drive\.google\.com/#'];

    /** @use HasFactory<BeneficiaryFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'full_name', 'date_of_birth', 'gender', 'phone', 'email', 'country',
        'region', 'category', 'description', 'status', 'assigned_to_user_id',
        'photo_path', 'notes', 'drive_folder_url', 'source_application_id',
        'public_sponsorship_id', 'public_sponsorship_slug', 'sponsorship_published', 'public_display_name', 'public_story', 'public_photo_path', 'public_country', 'guardian_consent_at', 'guardian_consent_reference',
        'public_community_story', 'public_country_story',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'guardian_consent_at' => 'datetime',
            'sponsorship_published' => 'boolean',
            'category' => SupportCategory::class,
            'status' => SupportStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $beneficiary): void {
            if ($beneficiary->exists && filled($beneficiary->getOriginal('public_sponsorship_slug'))) {
                $beneficiary->public_sponsorship_slug = $beneficiary->getOriginal('public_sponsorship_slug');
            } elseif (filled($beneficiary->public_display_name)) {
                $base = Str::limit(Str::slug(Str::before($beneficiary->public_display_name, ' (Demo)')), 80, '') ?: 'child';
                $slug = $base;

                while (static::withTrashed()->where('public_sponsorship_slug', $slug)->where('id', '!=', $beneficiary->getKey() ?? 0)->exists()) {
                    $slug = $base.'-'.Str::lower(Str::random(8));
                }

                $beneficiary->public_sponsorship_slug = $slug;
            }
            if (! $beneficiary->public_sponsorship_id) {
                $beneficiary->public_sponsorship_id = (string) Str::uuid();
            }
            if ($beneficiary->exists && $beneficiary->isDirty('public_sponsorship_id')) {
                $beneficiary->public_sponsorship_id = $beneficiary->getOriginal('public_sponsorship_id') ?: $beneficiary->public_sponsorship_id;
            }
            if (! $beneficiary->guardian_consent_at || ! filled($beneficiary->guardian_consent_reference)) {
                $beneficiary->sponsorship_published = false;
            }
        });
    }

    /** @param Builder<Beneficiary> $query */
    public function scopePublicSponsorship(Builder $query): void
    {
        $query->active()->where('sponsorship_published', true)
            ->whereNotNull('public_sponsorship_id')
            ->whereNotNull('public_sponsorship_slug')
            ->whereNotNull('guardian_consent_at')->where('guardian_consent_at', '<=', now())
            ->whereDate('date_of_birth', '>', today()->subYears(18)->toDateString())
            ->whereDate('date_of_birth', '<=', today()->toDateString());
        foreach (['public_display_name', 'public_story', 'public_photo_path', 'public_country', 'guardian_consent_reference'] as $field) {
            $query->whereNotNull($field)->where($field, '!=', '');
        }
        $query->where('public_photo_path', 'like', 'sponsorship/%')
            ->where('public_photo_path', 'not like', '%..%')
            ->where('public_photo_path', 'not like', '%\\%');
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

    /** @return HasMany<SponsorshipPhoto, $this> */
    public function sponsorshipPhotos(): HasMany
    {
        return $this->hasMany(SponsorshipPhoto::class)->orderBy('sort')->orderBy('id');
    }

    /** @return HasMany<Sponsorship, $this> */
    public function sponsorships(): HasMany
    {
        return $this->hasMany(Sponsorship::class);
    }

    public function isSponsored(): bool
    {
        return $this->sponsorships()->active()->exists();
    }

    /**
     * Adds an `is_sponsored` attribute, without loading any sponsor details.
     *
     * @param  Builder<Beneficiary>  $query
     */
    public function scopeWithSponsoredFlag(Builder $query): void
    {
        $query->withExists(['sponsorships as is_sponsored' => fn ($sponsorships) => $sponsorships->active()]);
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
