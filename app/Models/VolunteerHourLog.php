<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerHourLog extends Model
{
    protected $fillable = ['volunteer_id', 'event_id', 'hours', 'served_on', 'notes', 'recorded_by'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['hours' => 'decimal:2', 'served_on' => 'date'];
    }

    /** @return BelongsTo<Volunteer, $this> */
    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(Volunteer::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
