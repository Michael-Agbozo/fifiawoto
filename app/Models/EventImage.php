<?php

namespace App\Models;

use Database\Factories\EventImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventImage extends Model
{
    /** @use HasFactory<EventImageFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'path',
        'caption',
        'sort',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
