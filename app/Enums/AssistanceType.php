<?php

namespace App\Enums;

enum AssistanceType: string
{
    case Widow = 'widow';
    case Education = 'education';
    case Medical = 'medical';
    case Disability = 'disability';
    case Community = 'community';

    public function label(): string
    {
        return match ($this) {
            self::Widow => 'Widow Support',
            self::Education => 'Education Support',
            self::Medical => 'Medical Support',
            self::Disability => 'Disability Assistance',
            self::Community => 'Community Support',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
