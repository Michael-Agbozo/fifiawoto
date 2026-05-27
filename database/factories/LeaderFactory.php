<?php

namespace Database\Factories;

use App\Models\Leader;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Leader>
 */
class LeaderFactory extends Factory
{
    protected $model = Leader::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement(['Board of Directors', 'Board of Advisors', 'Founder · Board Chair']),
            'photo_path' => null,
            'bio' => null,
            'is_published' => true,
            'sort' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function unpublished(): self
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
