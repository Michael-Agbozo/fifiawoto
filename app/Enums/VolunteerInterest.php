<?php

namespace App\Enums;

enum VolunteerInterest: string
{
    case CommunityOutreach = 'community_outreach';
    case EducationPrograms = 'education_programs';
    case EventSupport = 'event_support';
    case AdministrativeSupport = 'administrative_support';
    case MediaCommunications = 'media_communications';

    public function label(): string
    {
        return match ($this) {
            self::CommunityOutreach => 'Community Outreach',
            self::EducationPrograms => 'Education Programs',
            self::EventSupport => 'Event Support',
            self::AdministrativeSupport => 'Administrative Support',
            self::MediaCommunications => 'Media and Communications',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
