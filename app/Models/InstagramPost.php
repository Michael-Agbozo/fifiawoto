<?php

namespace App\Models;

use App\Enums\InstagramSource;
use Database\Factories\InstagramPostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramPost extends Model
{
    /** @use HasFactory<InstagramPostFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id', 'permalink', 'caption', 'media_url', 'media_type',
        'thumbnail_url', 'posted_at', 'is_approved', 'is_hidden', 'source',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'is_approved' => 'boolean',
            'is_hidden' => 'boolean',
            'source' => InstagramSource::class,
        ];
    }

    /** @param Builder<InstagramPost> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_approved', true)->where('is_hidden', false);
    }
}
