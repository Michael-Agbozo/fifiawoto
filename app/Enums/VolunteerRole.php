<?php

namespace App\Enums;

enum VolunteerRole: string
{
    case Event = 'event';
    case CommunityOutreach = 'community_outreach';
    case Administrative = 'administrative';
    case Media = 'media';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'Event Volunteer',
            self::CommunityOutreach => 'Community Outreach Volunteer',
            self::Administrative => 'Administrative Volunteer',
            self::Media => 'Media Volunteer',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
