<?php

namespace App\Enums;

enum NewsletterCampaignStatus: string
{
    case Draft = 'draft';
    case Sending = 'sending';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
        };
    }

    public function palette(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sending => 'amber',
            self::Sent => 'green',
        };
    }
}
