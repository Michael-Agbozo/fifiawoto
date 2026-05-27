<?php

namespace App\Enums;

enum InstagramSource: string
{
    case Manual = 'manual';
    case Api = 'api';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Api => 'API',
        };
    }
}
