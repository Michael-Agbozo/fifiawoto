<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Archived => 'Archived',
        };
    }

    public function palette(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::InProgress => 'blue',
            self::Resolved => 'green',
            self::Archived => 'gray',
        };
    }
}
