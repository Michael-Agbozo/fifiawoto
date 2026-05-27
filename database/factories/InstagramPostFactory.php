<?php

namespace Database\Factories;

use App\Enums\InstagramSource;
use App\Models\InstagramPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstagramPost>
 */
class InstagramPostFactory extends Factory
{
    protected $model = InstagramPost::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $id = $this->faker->unique()->uuid();

        return [
            'external_id' => $id,
            'permalink' => 'https://instagram.com/p/'.substr($id, 0, 11),
            'caption' => $this->faker->sentence(),
            'media_url' => null,
            'media_type' => 'IMAGE',
            'thumbnail_url' => null,
            'posted_at' => now()->subDays($this->faker->numberBetween(0, 60)),
            'is_approved' => true,
            'is_hidden' => false,
            'source' => InstagramSource::Manual->value,
        ];
    }
}
