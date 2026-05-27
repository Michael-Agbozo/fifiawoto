<?php

namespace App\Models;

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use Database\Factories\VolunteerApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerApplication extends Model
{
    /** @use HasFactory<VolunteerApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'country',
        'interests',
        'availability',
        'skills',
        'motivation',
        'consented_at',
        'status',
        'reviewer_id',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'consented_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'status' => VolunteerApplicationStatus::class,
            'availability' => VolunteerAvailability::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
