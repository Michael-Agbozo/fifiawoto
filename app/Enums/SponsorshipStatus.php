<?php

namespace App\Enums;

enum SponsorshipStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Ended = 'ended';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Ended => 'Ended',
            self::Declined => 'Declined',
        };
    }

    public function palette(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Active => 'green',
            self::Ended, self::Declined => 'gray',
        };
    }
}
