<?php

namespace App\Enums;

enum MediaCategory: string
{
    case CommunityOutreach = 'community_outreach';
    case EducationPrograms = 'education_programs';
    case Events = 'events';
    case Volunteers = 'volunteers';

    public function label(): string
    {
        return match ($this) {
            self::CommunityOutreach => 'Community Outreach',
            self::EducationPrograms => 'Education Programs',
            self::Events => 'Events',
            self::Volunteers => 'Volunteers',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
