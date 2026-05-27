<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Bypass User's mass-assignment guard so tests/seeders can still set
     * `role` and `permissions` (these are guarded on the model so no
     * controller can mass-assign them from request input).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newModel(array $attributes = []): User
    {
        return (new User)->forceFill($attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Volunteer->value,
            'phone' => null,
            'avatar_path' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function withRole(UserRole $role): static
    {
        return $this->state(fn () => ['role' => $role->value]);
    }

    public function superAdmin(): static
    {
        return $this->withRole(UserRole::SuperAdmin);
    }

    public function owner(): static
    {
        return $this->withRole(UserRole::Owner);
    }

    public function foundationStaff(): static
    {
        return $this->withRole(UserRole::FoundationStaff);
    }

    public function volunteerCoordinator(): static
    {
        return $this->withRole(UserRole::VolunteerCoordinator);
    }

    public function mediaManager(): static
    {
        return $this->withRole(UserRole::MediaManager);
    }
}
