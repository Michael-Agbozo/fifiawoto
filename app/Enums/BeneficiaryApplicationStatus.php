<?php

namespace App\Enums;

enum BeneficiaryApplicationStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function palette(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::UnderReview => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'inbox',
            self::UnderReview => 'sparkles',
            self::Approved => 'shield',
            self::Rejected => 'inbox',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
