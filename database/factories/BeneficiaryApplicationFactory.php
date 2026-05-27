<?php

namespace Database\Factories;

use App\Enums\AssistanceType;
use App\Enums\BeneficiaryApplicationStatus;
use App\Models\BeneficiaryApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryApplication>
 */
class BeneficiaryApplicationFactory extends Factory
{
    protected $model = BeneficiaryApplication::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->optional()->safeEmail(),
            'country' => $this->faker->randomElement(['Ghana', 'Togo', 'Benin', 'United States']),
            'region' => $this->faker->city(),
            'assistance_type' => $this->faker->randomElement(AssistanceType::cases())->value,
            'situation' => $this->faker->paragraphs(2, true),
            'status' => BeneficiaryApplicationStatus::New->value,
        ];
    }
}
