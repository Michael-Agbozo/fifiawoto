<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'starts_at',
        'ends_at',
        'location',
        'country',
        'description',
        'activities',
        'expected_impact',
        'volunteer_opportunities',
        'goal_cents',
        'hero_image_path',
        'status',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'goal_cents' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if (blank($event->slug) && filled($event->title)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<EventImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderBy('sort');
    }

    /**
     * @return HasMany<Donation, $this>
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function raisedCents(): int
    {
        return (int) $this->donations()->sum('amount_cents');
    }

    public function progressPercent(): int
    {
        if (! $this->goal_cents) {
            return 0;
        }

        return (int) min(100, round($this->raisedCents() / $this->goal_cents * 100));
    }

    /**
     * @param  Builder<Event>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', EventStatus::Published->value)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  Builder<Event>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');
    }
}
