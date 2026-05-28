<?php

namespace App\Enums;

enum VolunteerApplicationStatus: string
{
    case New = 'new';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function palette(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}
