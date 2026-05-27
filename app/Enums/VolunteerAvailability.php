<?php

namespace App\Enums;

enum VolunteerAvailability: string
{
    case Weekdays = 'weekdays';
    case Weekends = 'weekends';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::Weekdays => 'Weekdays',
            self::Weekends => 'Weekends',
            self::Flexible => 'Flexible',
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
