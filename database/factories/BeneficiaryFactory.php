<?php

namespace Database\Factories;

use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'date_of_birth' => $this->faker->dateTimeBetween('-80 years', '-5 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['female', 'male', 'other']),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->optional()->safeEmail(),
            'country' => $this->faker->randomElement(['Ghana', 'Togo', 'Benin', 'United States']),
            'region' => $this->faker->city(),
            'category' => $this->faker->randomElement(SupportCategory::cases())->value,
            'description' => $this->faker->paragraphs(2, true),
            'status' => SupportStatus::PendingReview->value,
            'assigned_to_user_id' => null,
            'photo_path' => null,
            'notes' => null,
            'source_application_id' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => ['status' => SupportStatus::Active->value]);
    }
}
