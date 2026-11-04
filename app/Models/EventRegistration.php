<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    protected $fillable = ['event_id', 'full_name', 'email', 'phone', 'party_size', 'consented_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['party_size' => 'integer', 'consented_at' => 'datetime'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
