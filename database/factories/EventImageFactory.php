<?php

namespace Database\Factories;

use App\Models\EventImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventImage>
 */
class EventImageFactory extends Factory
{
    protected $model = EventImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => 'events/placeholder/'.$this->faker->uuid().'.jpg',
            'caption' => $this->faker->sentence(5),
            'sort' => $this->faker->numberBetween(0, 20),
        ];
    }
}
