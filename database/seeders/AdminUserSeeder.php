<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Production-safe bootstrap of the Owner + Admin accounts.
 *
 * Reads env() directly on purpose: it is run as a one-off with the credentials passed
 * inline (`OWNER_PASSWORD=... php artisan db:seed --class=AdminUserSeeder --force`).
 * Real process env vars are still visible to env() when the config is cached, whereas
 * config() values would be frozen at `config:cache` time. Existing users are never modified.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->upsert(
            email: env('OWNER_EMAIL', 'owner@fifiawoto.org'),
            name: 'Super Admin',
            password: env('OWNER_PASSWORD'),
            role: UserRole::Owner,
        );

        $this->upsert(
            email: env('ADMIN_EMAIL', 'admin@fifiawoto.org'),
            name: 'Admin',
            password: env('ADMIN_PASSWORD'),
            role: UserRole::SuperAdmin,
        );
    }

    private function upsert(string $email, string $name, ?string $password, UserRole $role): void
    {
        if (! $password) {
            $this->command->warn("Skipping {$email} — no password env var set.");

            return;
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role->value,
                'email_verified_at' => now(),
            ],
        );
    }
}
