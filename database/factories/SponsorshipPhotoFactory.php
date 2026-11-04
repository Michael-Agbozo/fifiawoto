<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\SponsorshipPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SponsorshipPhoto>
 */
class SponsorshipPhotoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'path' => 'sponsorship/gallery/'.$this->faker->uuid().'.jpg',
            'caption' => $this->faker->optional()->sentence(6),
            'is_sensitive' => false,
            'sort' => 0,
        ];
    }

    public function sensitive(): self
    {
        return $this->state(fn () => ['is_sensitive' => true]);
    }
}
