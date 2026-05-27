<?php

namespace App\Models;

use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    protected $fillable = [
        'author_name', 'author_role', 'photo_path', 'quote', 'video_url', 'featured', 'sort',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /** @param Builder<Testimonial> $query */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('featured', true);
    }

    /** @param Builder<Testimonial> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('id');
    }
}
