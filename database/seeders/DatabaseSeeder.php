<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Local / staging demo data only. Creates demo accounts and sample content, so it
 * refuses to run in production — use AdminUserSeeder there instead. Anywhere except a
 * developer's machine it needs DEMO_PASSWORD, so `password` never reaches a public URL.
 * Must not use factories: deployed builds install without Faker (`composer --no-dev`).
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

        if (! env('DEMO_PASSWORD') && ! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Set DEMO_PASSWORD to seed demo accounts here: `DEMO_PASSWORD=... php artisan db:seed --force`.'
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
            DemoMediaSeeder::class,
            DemoSponsorshipSeeder::class,
            DemoActivitySeeder::class,
        ]);
    }

    /**
     * Create a demo account. Locally it uses `password` with a forced password change;
     * with DEMO_PASSWORD set the login is shared by reviewers, so no change is forced.
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
            'password' => Hash::make(env('DEMO_PASSWORD') ?: 'password'),
            'role' => $role->value,
            'email_verified_at' => now(),
            'force_password_change' => ! env('DEMO_PASSWORD'),
        ])->save();

        return $user;
    }
}
