<?php

namespace App\Enums;

enum ContactSubject: string
{
    case General = 'general';
    case Volunteer = 'volunteer';
    case Donation = 'donation';
    case Partnership = 'partnership';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General Inquiry',
            self::Volunteer => 'Volunteer Information',
            self::Donation => 'Donation Inquiry',
            self::Partnership => 'Partnership Opportunity',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
