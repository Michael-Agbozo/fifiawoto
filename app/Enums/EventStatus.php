<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function palette(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Published => 'green',
            self::Archived => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'inbox',
            self::Published => 'calendar',
            self::Archived => 'shield',
        };
    }
}
