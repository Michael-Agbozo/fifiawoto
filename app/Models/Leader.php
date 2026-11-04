<?php

namespace App\Models;

use Database\Factories\LeaderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leader extends Model
{
    /** @use HasFactory<LeaderFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'role', 'photo_path', 'bio', 'linkedin_url', 'facebook_url', 'instagram_url', 'x_url', 'is_published', 'sort',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * The person's own social profiles, keyed by network, leaving out any not filled in.
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return array_filter([
            'LinkedIn' => $this->linkedin_url,
            'Facebook' => $this->facebook_url,
            'Instagram' => $this->instagram_url,
            'X' => $this->x_url,
        ]);
    }

    /** @param Builder<Leader> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param Builder<Leader> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('id');
    }
}
