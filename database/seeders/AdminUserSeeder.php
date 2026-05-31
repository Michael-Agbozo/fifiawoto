<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        User::updateOrCreate(
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
