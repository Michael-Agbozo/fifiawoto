<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'author_name' => $this->faker->name(),
            'author_role' => $this->faker->randomElement(['Volunteer', 'Community Member', 'Beneficiary', 'Partner']),
            'photo_path' => null,
            'quote' => $this->faker->paragraph(2),
            'video_url' => null,
            'featured' => $this->faker->boolean(40),
            'sort' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function featured(): self
    {
        return $this->state(fn () => ['featured' => true]);
    }
}
