<?php

namespace App\Enums;

enum SupportStatus: string
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Active = 'active';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending Review',
            self::Approved => 'Approved',
            self::Active => 'Active Support',
            self::Completed => 'Completed',
        };
    }

    public function palette(): string
    {
        return match ($this) {
            self::PendingReview => 'amber',
            self::Approved => 'brand',
            self::Active => 'green',
            self::Completed => 'gold',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PendingReview => 'inbox',
            self::Approved => 'shield',
            self::Active => 'sparkles',
            self::Completed => 'heart',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
