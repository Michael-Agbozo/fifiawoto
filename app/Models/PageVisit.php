<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $fillable = ['path', 'source', 'medium', 'campaign', 'visitor_hash'];

    /** @return Builder<PageVisit> */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(13));
    }
}
