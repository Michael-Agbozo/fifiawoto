<?php

namespace App\Enums;

use App\Support\Permissions;

enum UserRole: string
{
    case Owner = 'owner';
    case SuperAdmin = 'super_admin';
    case FoundationStaff = 'foundation_staff';
    case VolunteerCoordinator = 'volunteer_coordinator';
    case MediaManager = 'media_manager';
    case Volunteer = 'volunteer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Super Admin',
            self::SuperAdmin => 'Admin',
            self::FoundationStaff => 'Foundation Staff',
            self::VolunteerCoordinator => 'Volunteer Coordinator',
            self::MediaManager => 'Media Manager',
            self::Volunteer => 'Volunteer',
        };
    }

    /**
     * Roles that can access the /admin area at all.
     *
     * @return array<int, self>
     */
    public static function adminRoles(): array
    {
        return [
            self::Owner,
            self::SuperAdmin,
            self::FoundationStaff,
            self::VolunteerCoordinator,
            self::MediaManager,
        ];
    }

    public function canAccessAdmin(): bool
    {
        return in_array($this, self::adminRoles(), true);
    }

    public function canManageBeneficiaries(): bool
    {
        return in_array($this, [self::Owner, self::SuperAdmin, self::FoundationStaff], true);
    }

    public function canManageEvents(): bool
    {
        return in_array($this, [self::Owner, self::SuperAdmin, self::FoundationStaff], true);
    }

    public function canManageDonations(): bool
    {
        return in_array($this, [self::Owner, self::SuperAdmin, self::FoundationStaff], true);
    }

    public function canManageVolunteers(): bool
    {
        return in_array($this, [self::Owner, self::SuperAdmin, self::VolunteerCoordinator], true);
    }

    public function canManageMedia(): bool
    {
        return in_array($this, [self::Owner, self::SuperAdmin, self::MediaManager], true);
    }

    public function canManageUsers(): bool
    {
        return in_array($this, [self::Owner, self::SuperAdmin], true);
    }

    public function canViewReports(): bool
    {
        return in_array($this, [self::Owner, self::SuperAdmin, self::FoundationStaff], true);
    }

    public function canViewSystemLogs(): bool
    {
        return $this === self::Owner;
    }

    /**
     * Default granular permission keys granted by this role.
     *
     * @return array<int, string>
     */
    public function defaultPermissions(): array
    {
        $all = Permissions::keys();

        return match ($this) {
            self::Owner => $all,
            self::SuperAdmin => array_values(array_filter($all, fn ($k) => ! str_starts_with($k, 'system_logs.'))),
            self::FoundationStaff => array_merge(
                self::crudFor('beneficiaries'),
                self::crudFor('beneficiary_applications'),
                self::crudFor('events'),
                self::crudFor('donations'),
                self::crudFor('newsletter'),
                ['reports.view', 'reports.export'],
                ['inbox.view', 'inbox.reply', 'inbox.delete'],
                ['volunteers.view', 'media.view', 'testimonials.view', 'instagram.view', 'leaders.view'],
            ),
            self::VolunteerCoordinator => array_merge(
                self::crudFor('volunteers'),
                ['beneficiaries.view', 'events.view', 'reports.view', 'inbox.view'],
            ),
            self::MediaManager => array_merge(
                self::crudFor('media'),
                self::crudFor('instagram'),
                self::crudFor('testimonials'),
                self::crudFor('leaders'),
                self::crudFor('newsletter'),
                ['events.view', 'inbox.view'],
            ),
            self::Volunteer => [],
        };
    }

    /**
     * @return array<int, string>
     */
    private static function crudFor(string $resource): array
    {
        return [
            $resource.'.view',
            $resource.'.create',
            $resource.'.update',
            $resource.'.delete',
        ];
    }
}
