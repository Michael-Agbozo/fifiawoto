<?php

namespace App\Enums;

enum SupportCategory: string
{
    case WidowSupport = 'widow_support';
    case ChildEducation = 'child_education';
    case Medical = 'medical';
    case Disability = 'disability';
    case Community = 'community';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WidowSupport => 'Widow Support',
            self::ChildEducation => 'Child Education',
            self::Medical => 'Medical Assistance',
            self::Disability => 'Disability Support',
            self::Community => 'Community Aid',
            self::Other => 'Other',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
