<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Local / demo data only. Creates well-known demo accounts and sample content,
 * so it refuses to run in production — use AdminUserSeeder there instead.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'DatabaseSeeder seeds demo accounts and must not run in production. '
                .'Use `php artisan db:seed --class=AdminUserSeeder --force` instead.'
            );
        }

        $this->seedUser('superadmin@fifiawoto.test', 'Super Admin', UserRole::Owner);
        $this->seedUser('admin@fifiawoto.test', 'Admin', UserRole::SuperAdmin);
        $this->seedUser('staff@fifiawoto.test', 'Foundation Staff', UserRole::FoundationStaff);
        $this->seedUser('volunteer-coord@fifiawoto.test', 'Volunteer Coordinator', UserRole::VolunteerCoordinator);
        $this->seedUser('media@fifiawoto.test', 'Media Manager', UserRole::MediaManager);

        $this->call([
            EventSeeder::class,
            VolunteerSeeder::class,
            BeneficiarySeeder::class,
            InstagramHighlightSeeder::class,
            LeaderSeeder::class,
        ]);
    }

    /**
     * Create a demo account with the password `password` and a forced password change.
     * Existing accounts are left untouched so re-seeding never resets a real password.
     */
    private function seedUser(string $email, string $name, UserRole $role): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        if ($user->exists) {
            return $user;
        }

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make('password'),
            'role' => $role->value,
            'email_verified_at' => now(),
            'force_password_change' => true,
        ])->save();

        return $user;
    }
}
