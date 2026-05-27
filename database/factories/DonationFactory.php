<?php

namespace Database\Factories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donor_name' => $this->faker->name(),
            'donor_email' => $this->faker->safeEmail(),
            'amount_cents' => $this->faker->randomElement([2500, 5000, 10000, 25000, 50000, 100000]),
            'currency' => 'USD',
            'payment_method' => 'cash',
            'external_reference' => null,
            'received_at' => $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'notes' => null,
        ];
    }
}
