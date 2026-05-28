<?php

namespace App\Models;

use App\Enums\MediaCategory;
use App\Enums\MediaType;
use Database\Factories\MediaItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaItem extends Model
{
    /** @use HasFactory<MediaItemFactory> */
    use HasFactory;

    protected $fillable = [
        'type', 'category', 'event_id', 'disk', 'path', 'poster_path', 'caption', 'sort',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'category' => MediaCategory::class,
            'sort' => 'integer',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
