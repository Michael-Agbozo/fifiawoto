<?php

namespace App\Models;

use App\Enums\VolunteerRole;
use Database\Factories\VolunteerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Volunteer extends Model
{
    /** @use HasFactory<VolunteerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'application_id', 'full_name', 'email', 'phone',
        'country', 'role', 'assigned_at', 'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'role' => VolunteerRole::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<VolunteerApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(VolunteerApplication::class, 'application_id');
    }
}
