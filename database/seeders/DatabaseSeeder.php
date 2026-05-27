<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUser(
            ['email' => 'superadmin@fifiawoto.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Owner->value,
                'email_verified_at' => now(),
            ],
        );

        $this->seedUser(
            ['email' => 'admin@fifiawoto.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin->value,
                'email_verified_at' => now(),
            ],
        );

        $this->seedUser(
            ['email' => 'staff@fifiawoto.test'],
            [
                'name' => 'Foundation Staff',
                'password' => Hash::make('password'),
                'role' => UserRole::FoundationStaff->value,
                'email_verified_at' => now(),
            ],
        );

        $this->seedUser(
            ['email' => 'volunteer-coord@fifiawoto.test'],
            [
                'name' => 'Volunteer Coordinator',
                'password' => Hash::make('password'),
                'role' => UserRole::VolunteerCoordinator->value,
                'email_verified_at' => now(),
            ],
        );

        $this->seedUser(
            ['email' => 'media@fifiawoto.test'],
            [
                'name' => 'Media Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::MediaManager->value,
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            EventSeeder::class,
            VolunteerSeeder::class,
            BeneficiarySeeder::class,
            InstagramHighlightSeeder::class,
            LeaderSeeder::class,
        ]);
    }

    /**
     * @param  array<string, mixed>  $lookup
     * @param  array<string, mixed>  $values
     */
    private function seedUser(array $lookup, array $values): User
    {
        $user = User::query()->firstOrNew($lookup);
        $user->forceFill($values);
        $user->save();

        return $user;
    }
}
