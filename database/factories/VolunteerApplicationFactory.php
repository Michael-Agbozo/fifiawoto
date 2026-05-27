<?php

namespace Database\Factories;

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Models\VolunteerApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolunteerApplication>
 */
class VolunteerApplicationFactory extends Factory
{
    protected $model = VolunteerApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'country' => $this->faker->randomElement(['Ghana', 'Togo', 'Benin', 'United States']),
            'interests' => $this->faker->randomElements(
                array_map(fn (VolunteerInterest $i) => $i->value, VolunteerInterest::cases()),
                $this->faker->numberBetween(1, 3),
            ),
            'availability' => $this->faker->randomElement(VolunteerAvailability::cases())->value,
            'skills' => $this->faker->sentence(8),
            'motivation' => $this->faker->paragraph(),
            'consented_at' => now(),
            'status' => VolunteerApplicationStatus::New->value,
        ];
    }
}
