<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class SiteConversion extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    public const LABELS = [
        'sponsorship_request' => 'Sponsorship requests',
        'donation_enquiry' => 'Donation enquiries',
        'volunteer_application' => 'Volunteer applications',
        'newsletter_signup' => 'Newsletter signups',
        'event_registration' => 'Event sign-ups',
    ];

    protected $fillable = ['type', 'source', 'medium', 'campaign'];

    /** @return Builder<SiteConversion> */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(13));
    }
}
