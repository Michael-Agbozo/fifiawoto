<?php

namespace Database\Factories;

use App\Enums\VolunteerRole;
use App\Models\Volunteer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Volunteer>
 */
class VolunteerFactory extends Factory
{
    protected $model = Volunteer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'application_id' => null,
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'country' => $this->faker->randomElement(['Ghana', 'Togo', 'Benin', 'United States']),
            'role' => $this->faker->randomElement(VolunteerRole::cases())->value,
            'assigned_at' => now()->subDays($this->faker->numberBetween(0, 90)),
            'notes' => null,
        ];
    }
}
