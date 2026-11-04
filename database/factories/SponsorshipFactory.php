<?php

namespace Database\Factories;

use App\Enums\SponsorshipStatus;
use App\Models\Beneficiary;
use App\Models\Sponsorship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsorship>
 */
class SponsorshipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'sponsor_name' => fake()->name(),
            'sponsor_email' => fake()->safeEmail(),
            'monthly_amount_cents' => 5000,
            'currency' => 'USD',
            'status' => SponsorshipStatus::Pending,
        ];
    }

    public function active(?string $startedOn = null): static
    {
        return $this->state(fn () => [
            'status' => SponsorshipStatus::Active,
            'started_on' => $startedOn ?? today()->toDateString(),
            'next_due_on' => $startedOn ?? today()->toDateString(),
        ]);
    }
}
