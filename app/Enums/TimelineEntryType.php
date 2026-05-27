<?php

namespace App\Enums;

enum TimelineEntryType: string
{
    case ApplicationReceived = 'application_received';
    case CaseReviewed = 'case_reviewed';
    case SupportApproved = 'support_approved';
    case AidDelivered = 'aid_delivered';
    case FollowupVisit = 'followup_visit';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationReceived => 'Application received',
            self::CaseReviewed => 'Case reviewed',
            self::SupportApproved => 'Support approved',
            self::AidDelivered => 'Aid delivered',
            self::FollowupVisit => 'Follow-up visit',
            self::Note => 'Note',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
