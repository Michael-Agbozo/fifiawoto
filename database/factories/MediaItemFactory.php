<?php

namespace Database\Factories;

use App\Enums\MediaCategory;
use App\Enums\MediaType;
use App\Models\MediaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaItem>
 */
class MediaItemFactory extends Factory
{
    protected $model = MediaItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => MediaType::Image->value,
            'category' => $this->faker->randomElement(MediaCategory::cases())->value,
            'event_id' => null,
            'disk' => 'public',
            'path' => 'media/'.$this->faker->uuid().'.jpg',
            'poster_path' => null,
            'caption' => $this->faker->sentence(),
            'sort' => $this->faker->numberBetween(0, 100),
        ];
    }
}
