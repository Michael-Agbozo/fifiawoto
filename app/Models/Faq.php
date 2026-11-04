<?php

namespace App\Models;

use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    /**
     * Public pages that show an FAQ list, keyed by the value stored in `page`.
     *
     * @var array<string, string>
     */
    public const PAGES = [
        'contact' => 'Contact page',
        'sponsorship' => 'Sponsor a Child page',
    ];

    protected $fillable = [
        'page', 'question', 'answer', 'is_published', 'sort',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /** @param Builder<Faq> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param Builder<Faq> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('id');
    }

    /** @param Builder<Faq> $query */
    public function scopeOnPage(Builder $query, string $page): void
    {
        $query->where('page', $page);
    }
}
